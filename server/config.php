<?php
// config.php - 数据库配置
require_once 'SecurityConfig.php';

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        // 从安全配置获取数据库参数
        $config = SecurityConfig::getDbConfig();
        $this->host = $config['host'];
        $this->db_name = $config['name'];
        $this->username = $config['username'];
        $this->password = $config['password'];
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
                ]
            );
        } catch(PDOException $exception) {
            // 安全错误处理
            error_log("Database connection failed: " . $exception->getMessage());
            throw new Exception("数据库连接失败，请检查配置");
        }
        return $this->conn;
    }
}
?>