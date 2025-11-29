<?php
/**
 * 安装检查类
 * 用于检查系统是否已安装
 */

class InstallChecker {
    // 安装锁定文件路径
    private static $lockFile = __DIR__ . '/../install/.installed';
    // 配置文件路径
    private static $envFile = __DIR__ . '/../install/.env';
    
    /**
     * 检查系统是否已安装
     * @return bool 返回true表示已安装，false表示未安装
     */
    public static function isInstalled() {
        // 检查安装锁定文件
        if (file_exists(self::$lockFile)) {
            return true;
        }
        
        // 检查配置文件是否存在
        if (!file_exists(self::$envFile)) {
            return false;
        }
        
        // 检查数据库是否已初始化
        try {
            require_once __DIR__ . '/SecurityConfig.php';
            $config = SecurityConfig::getDbConfig();
            $host = $config['host'];
            $dbname = $config['name'];
            $username = $config['username'];
            $password = $config['password'];
            
            // 解析host（可能包含端口）
            $hostParts = explode(':', $host);
            $dbHost = $hostParts[0];
            $dbPort = isset($hostParts[1]) ? $hostParts[1] : '3306';
            
            $conn = new PDO(
                "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4",
                $username,
                $password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            // 检查数据库是否存在且有表
            $stmt = $conn->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '{$dbname}'");
            $tableCount = $stmt->fetchColumn();
            
            if ($tableCount > 0) {
                // 创建安装锁定文件
                self::createLockFile();
                return true;
            }
        } catch (Exception $e) {
            // 数据库未配置或连接失败，继续安装流程
            return false;
        }
        
        return false;
    }
    
    /**
     * 创建安装锁定文件
     */
    public static function createLockFile() {
        $lockDir = dirname(self::$lockFile);
        if (!is_dir($lockDir)) {
            mkdir($lockDir, 0755, true);
        }
        
        $lockContent = "安装时间: " . date('Y-m-d H:i:s') . "\n";
        $lockContent .= "安装锁定文件，请勿删除此文件。\n";
        $lockContent .= "如需重新安装，请删除此文件。\n";
        
        file_put_contents(self::$lockFile, $lockContent);
        // 设置文件权限（只读）
        chmod(self::$lockFile, 0444);
    }
    
    /**
     * 检查安装目录是否可访问（用于安装完成后禁止访问）
     */
    public static function canAccessInstall() {
        // 如果已安装，检查是否有强制访问参数
        if (self::isInstalled()) {
            // 在生产环境中，应该完全禁止访问
            // 但为了调试，允许通过特殊参数访问（仅限本地）
            if (isset($_GET['force_install']) && 
                ($_SERVER['REMOTE_ADDR'] === '127.0.0.1' || $_SERVER['REMOTE_ADDR'] === '::1')) {
                return true;
            }
            return false;
        }
        return true;
    }
    
    /**
     * 重定向到安装页面
     */
    public static function redirectToInstall() {
        $installUrl = '/install/';
        header("Location: {$installUrl}");
        exit;
    }
}



