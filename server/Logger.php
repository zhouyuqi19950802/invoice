<?php
// Logger.php - 系统日志管理类
require_once 'config.php';
require_once 'IpLocation.php';

class Logger {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * 记录系统日志
     */
    public function log($userId, $username, $action, $description = '', $targetType = '', $targetId = null, $status = 1, $errorMessage = '') {
        try {
            // 获取真实IP地址
            $ipAddress = $this->getRealIpAddress();
            
            // 获取用户代理
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $sql = "INSERT INTO system_logs (F_user_id, F_username, F_action, F_description, F_ip_address, F_user_agent, F_target_type, F_target_id, F_status, F_error_message) 
                    VALUES (:user_id, :username, :action, :description, :ip_address, :user_agent, :target_type, :target_id, :status, :error_message)";
            
            $stmt = $this->conn->prepare($sql);
            
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':action', $action);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':ip_address', $ipAddress);
            $stmt->bindParam(':user_agent', $userAgent);
            $stmt->bindParam(':target_type', $targetType);
            $stmt->bindParam(':target_id', $targetId);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':error_message', $errorMessage);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Logger error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 记录登录日志
     */
    public function logLogin($userId, $username, $status = 1, $errorMessage = '') {
        $description = $status ? '用户登录成功' : '用户登录失败';
        if (!$status && $errorMessage) {
            $description .= ': ' . $errorMessage;
        }
        
        return $this->log($userId, $username, 'LOGIN', $description, '', null, $status, $errorMessage);
    }
    
    /**
     * 记录登出日志
     */
    public function logLogout($userId, $username) {
        return $this->log($userId, $username, 'LOGOUT', '用户退出登录');
    }
    
    /**
     * 记录发票操作日志
     */
    public function logInvoice($userId, $username, $action, $invoiceId, $description = '', $status = 1, $errorMessage = '') {
        return $this->log($userId, $username, $action, $description, 'invoice', $invoiceId, $status, $errorMessage);
    }
    
    /**
     * 记录用户管理操作日志
     */
    public function logUser($userId, $username, $action, $targetUserId, $description = '', $status = 1, $errorMessage = '') {
        return $this->log($userId, $username, $action, $description, 'user', $targetUserId, $status, $errorMessage);
    }
    
    /**
     * 获取日志列表
     */
    public function getLogs($page = 1, $limit = 5, $filters = []) {
        try {
            $offset = ($page - 1) * $limit;
            
            // 构建查询条件
            $where = [];
            $params = [];
            
            if (!empty($filters['action'])) {
                $where[] = "sl.F_action = :action";
                $params[':action'] = $filters['action'];
            }
            
            if (!empty($filters['username'])) {
                $where[] = "sl.F_username LIKE :username";
                $params[':username'] = '%' . $filters['username'] . '%';
            }
            
            if (!empty($filters['ip_address'])) {
                $where[] = "sl.F_ip_address LIKE :ip_address";
                $params[':ip_address'] = '%' . $filters['ip_address'] . '%';
            }
            
            if (isset($filters['status']) && $filters['status'] !== '') {
                $where[] = "sl.F_status = :status";
                $params[':status'] = $filters['status'];
            }
            
            if (!empty($filters['start_date'])) {
                $where[] = "sl.F_create_time >= :start_date";
                $params[':start_date'] = $filters['start_date'] . ' 00:00:00';
            }
            
            if (!empty($filters['end_date'])) {
                $where[] = "sl.F_create_time <= :end_date";
                $params[':end_date'] = $filters['end_date'] . ' 23:59:59';
            }
            
            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            
            // 获取总数
            $countSql = "SELECT COUNT(*) as total FROM system_logs sl $whereClause";
            $countStmt = $this->conn->prepare($countSql);
            
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // 获取数据
            $sql = "SELECT sl.*, u.F_realname 
                    FROM system_logs sl 
                    LEFT JOIN users u ON sl.F_user_id = u.F_id 
                    $whereClause 
                    ORDER BY sl.F_create_time DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 批量获取IP地理位置（容错处理，不影响日志列表显示）
            if (!empty($logs)) {
                try {
                    require_once 'IpLocation.php';
                    $ipLocation = new IpLocation();
                    $ips = array_map(function($log) {
                        return $log['F_ip_address'] ?? '-';
                    }, $logs);
                    
                    // 过滤掉空值和无效IP
                    $ips = array_filter(array_unique($ips), function($ip) {
                        return !empty($ip) && $ip !== '-';
                    });
                    
                    $locations = [];
                    if (!empty($ips)) {
                        $locations = $ipLocation->getBatchLocations($ips);
                    }
                    
                    // 为每条日志添加地理位置信息
                    foreach ($logs as &$log) {
                        $ip = $log['F_ip_address'] ?? '-';
                        if ($ip !== '-' && isset($locations[$ip])) {
                            $log['F_ip_location'] = $locations[$ip];
                        } else {
                            $log['F_ip_location'] = null;
                        }
                    }
                    unset($log); // 释放引用
                } catch (Exception $e) {
                    // IP地理位置查询失败不影响日志显示
                    error_log("Get IP locations error: " . $e->getMessage());
                    // 为所有日志设置默认值
                    foreach ($logs as &$log) {
                        $log['F_ip_location'] = null;
                    }
                    unset($log);
                }
            }
            
            return [
                'logs' => $logs,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ];
            
        } catch (PDOException $e) {
            error_log("Get logs error: " . $e->getMessage());
            return [
                'logs' => [],
                'total' => 0,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => 0
            ];
        }
    }
    
    /**
     * 获取操作类型列表
     */
    public function getActionTypes() {
        try {
            $sql = "SELECT DISTINCT F_action FROM system_logs ORDER BY F_action";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Get action types error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 清理旧日志
     */
    public function cleanOldLogs($days = 90) {
        try {
            $sql = "DELETE FROM system_logs WHERE F_create_time < DATE_SUB(NOW(), INTERVAL :days DAY)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':days', $days, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Clean old logs error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 获取真实IP地址
     */
    private function getRealIpAddress() {
        $ipKeys = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                // 验证IP地址格式
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * 获取统计信息
     */
    public function getStatistics($days = 30) {
        try {
            $stats = [];
            
            // 今日登录次数
            $sql = "SELECT COUNT(*) as count FROM system_logs WHERE F_action = 'LOGIN' AND DATE(F_create_time) = CURDATE()";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $stats['today_logins'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // 本周登录次数
            $sql = "SELECT COUNT(*) as count FROM system_logs WHERE F_action = 'LOGIN' AND F_create_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $stats['week_logins'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // 本月登录次数
            $sql = "SELECT COUNT(*) as count FROM system_logs WHERE F_action = 'LOGIN' AND F_create_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $stats['month_logins'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // 活跃用户数
            $sql = "SELECT COUNT(DISTINCT F_user_id) as count FROM system_logs WHERE F_create_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $stats['active_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // 失败登录次数
            $sql = "SELECT COUNT(*) as count FROM system_logs WHERE F_action = 'LOGIN' AND F_status = 0 AND F_create_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $stats['failed_logins'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            return $stats;
            
        } catch (PDOException $e) {
            error_log("Get statistics error: " . $e->getMessage());
            return [];
        }
    }
}
?>