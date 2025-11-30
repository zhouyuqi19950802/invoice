<?php
// IpLocation.php - IP地理位置查询类
require_once 'config.php';

class IpLocation {
    private $conn;
    private $cacheTable = 'ip_location_cache';
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->initCacheTable();
    }
    
    /**
     * 初始化缓存表
     */
    private function initCacheTable() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS {$this->cacheTable} (
                F_ip VARCHAR(45) PRIMARY KEY,
                F_location VARCHAR(255) NOT NULL,
                F_update_time DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_update_time (F_update_time)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='IP地理位置缓存表'";
            $this->conn->exec($sql);
        } catch (PDOException $e) {
            error_log("Init IP location cache table error: " . $e->getMessage());
        }
    }
    
    /**
     * 批量获取IP地理位置
     * @param array $ips IP地址数组
     * @return array IP地址 => 地理位置 的关联数组
     */
    public function getBatchLocations(array $ips) {
        if (empty($ips)) {
            return [];
        }
        
        $result = [];
        $ipsToQuery = [];
        
        // 先从缓存中查找
        foreach ($ips as $ip) {
            if (empty($ip) || $ip === '-') {
                $result[$ip] = '-';
                continue;
            }
            
            $cached = $this->getFromCache($ip);
            if ($cached !== false) {
                $result[$ip] = $cached;
            } else {
                $ipsToQuery[] = $ip;
            }
        }
        
        // 批量查询未缓存的IP
        if (!empty($ipsToQuery)) {
            $locations = $this->queryBatchLocations($ipsToQuery);
            foreach ($locations as $ip => $location) {
                $result[$ip] = $location;
                // 保存到缓存
                $this->saveToCache($ip, $location);
            }
        }
        
        return $result;
    }
    
    /**
     * 获取单个IP地理位置
     * @param string $ip IP地址
     * @return string 地理位置信息
     */
    public function getLocation($ip) {
        if (empty($ip) || $ip === '-') {
            return '-';
        }
        
        // 先查缓存
        $cached = $this->getFromCache($ip);
        if ($cached !== false) {
            return $cached;
        }
        
        // 查询地理位置
        $location = $this->queryLocation($ip);
        if ($location) {
            // 保存到缓存
            $this->saveToCache($ip, $location);
        }
        
        return $location ?: '-';
    }
    
    /**
     * 从缓存中获取
     */
    private function getFromCache($ip) {
        try {
            // 缓存有效期30天
            $sql = "SELECT F_location FROM {$this->cacheTable} 
                    WHERE F_ip = :ip AND F_update_time > DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':ip', $ip);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['F_location'] : false;
        } catch (PDOException $e) {
            error_log("Get IP location from cache error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 保存到缓存
     */
    private function saveToCache($ip, $location) {
        try {
            $sql = "INSERT INTO {$this->cacheTable} (F_ip, F_location) 
                    VALUES (:ip, :location)
                    ON DUPLICATE KEY UPDATE F_location = :location, F_update_time = NOW()";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':ip', $ip);
            $stmt->bindParam(':location', $location);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Save IP location to cache error: " . $e->getMessage());
        }
    }
    
    /**
     * 批量查询IP地理位置（使用免费API）
     */
    private function queryBatchLocations(array $ips) {
        $result = [];
        
        // 限制最多查询5个IP，避免超时
        $ips = array_slice($ips, 0, 5);
        
        // 逐个查询（免费API通常限制并发）
        foreach ($ips as $ip) {
            try {
                $location = $this->queryLocation($ip);
                $result[$ip] = $location ?: null;
            } catch (Exception $e) {
                error_log("Query location for IP {$ip} failed: " . $e->getMessage());
                $result[$ip] = null;
            }
            // 缩短延迟时间，避免请求超时
            usleep(50000); // 0.05秒
        }
        
        return $result;
    }
    
    /**
     * 查询IP地理位置
     * 使用多个免费API，如果某个失败则尝试下一个
     */
    private function queryLocation($ip) {
        // 检查是否为有效的IP地址
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return null;
        }
        
        // 检查是否为本地IP和私有IP
        $isPrivate = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
        if ($isPrivate) {
            return '本地/内网';
        }
        
        // 尝试多个API源
        $apis = [
            [$this, 'queryFromIpApi'],
            [$this, 'queryFromIpApiCo'],
        ];
        
        foreach ($apis as $api) {
            $location = call_user_func($api, $ip);
            if ($location) {
                return $location;
            }
        }
        
        return null;
    }
    
    /**
     * 从 ip-api.com 查询（免费，无需密钥）
     */
    private function queryFromIpApi($ip) {
        // 检查curl扩展是否可用
        if (!function_exists('curl_init')) {
            error_log("cURL extension not available for IP location query");
            return null;
        }
        
        try {
            $url = "http://ip-api.com/json/{$ip}?lang=zh-CN&fields=status,message,country,regionName,city";
            
            $ch = curl_init();
            if ($ch === false) {
                return null;
            }
            
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 2, // 缩短超时时间
                CURLOPT_CONNECTTIMEOUT => 1,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);
            
            $response = @curl_exec($ch);
            
            if ($response === false) {
                $error = curl_error($ch);
                curl_close($ch);
                if ($error) {
                    error_log("cURL error querying IP {$ip}: " . $error);
                }
                return null;
            }
            
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200 || !$response) {
                return null;
            }
            
            $data = @json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("JSON decode error for IP {$ip}: " . json_last_error_msg());
                return null;
            }
            
            if (isset($data['status']) && $data['status'] === 'success') {
                $parts = [];
                if (!empty($data['country'])) {
                    $parts[] = $data['country'];
                }
                if (!empty($data['regionName'])) {
                    $parts[] = $data['regionName'];
                }
                if (!empty($data['city'])) {
                    $parts[] = $data['city'];
                }
                
                return !empty($parts) ? implode('', $parts) : null;
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Query IP location from ip-api.com error for IP {$ip}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 从 ipapi.co 查询（备用）
     */
    private function queryFromIpApiCo($ip) {
        try {
            $url = "https://ipapi.co/{$ip}/json/";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'User-Agent: Mozilla/5.0'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200 || !$response) {
                return null;
            }
            
            $data = json_decode($response, true);
            
            if (isset($data['error'])) {
                return null;
            }
            
            $parts = [];
            if (!empty($data['country_name'])) {
                $parts[] = $data['country_name'];
            }
            if (!empty($data['region'])) {
                $parts[] = $data['region'];
            }
            if (!empty($data['city'])) {
                $parts[] = $data['city'];
            }
            
            return !empty($parts) ? implode('', $parts) : null;
        } catch (Exception $e) {
            error_log("Query IP location from ipapi.co error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 清理旧缓存（30天前的）
     */
    public function cleanOldCache() {
        try {
            $sql = "DELETE FROM {$this->cacheTable} WHERE F_update_time < DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Clean old IP location cache error: " . $e->getMessage());
            return false;
        }
    }
}
?>

