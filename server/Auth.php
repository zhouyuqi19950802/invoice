<?php
// Auth.php - 用户认证类
require_once 'SecurityConfig.php';
require_once 'config.php';
require_once 'Logger.php';

// 配置安全的Session
if (!SecurityConfig::configureSession()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Session配置失败']);
    exit;
}

class Auth {
    private $conn;
    private $logger;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->logger = new Logger();
    }
    
    // 用户登录
    public function login($username, $password) {
        try {
            // 输入验证和清理
            $username = SecurityConfig::sanitizeInput($username, 'string');
            $password = SecurityConfig::sanitizeInput($password, 'string');
            
            if (!$username || !$password) {
                $this->logger->logLogin(null, $username, 0, '用户名和密码不能为空');
                return [
                    'success' => false,
                    'message' => '用户名和密码不能为空'
                ];
            }
            
            // 检查登录速率限制
            $rateCheck = SecurityConfig::checkLoginRate($username);
            if (!$rateCheck['allowed']) {
                $this->logger->logLogin(null, $username, 0, $rateCheck['message']);
                return [
                    'success' => false,
                    'message' => $rateCheck['message']
                ];
            }
            
            $query = "SELECT F_id, F_username, F_password, F_realname, F_role, F_status 
                      FROM users 
                      WHERE F_username = :username AND F_status = 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // 验证密码
                if (password_verify($password, $user['F_password'])) {
                    // 记录成功登录
                    SecurityConfig::recordLoginAttempt($username, true);
                    $this->logger->logLogin($user['F_id'], $username, 1);
                    
                    // 重新生成Session ID防止劫持
                    session_regenerate_id(true);
                    
                    // 登录成功，设置会话
                    $_SESSION['user_id'] = $user['F_id'];
                    $_SESSION['username'] = $user['F_username'];
                    $_SESSION['realname'] = $user['F_realname'];
                    $_SESSION['role'] = $user['F_role'];
                    $_SESSION['login_time'] = time();
                    
                    return [
                        'success' => true,
                        'message' => '登录成功',
                        'user' => [
                            'F_id' => $user['F_id'],
                            'F_username' => $user['F_username'],
                            'F_realname' => $user['F_realname'],
                            'F_role' => $user['F_role']
                        ]
                    ];
                }
            }
            
            // 记录失败登录
            SecurityConfig::recordLoginAttempt($username, false);
            $this->logger->logLogin(null, $username, 0, '用户名或密码错误');
            
            return [
                'success' => false,
                'message' => '用户名或密码错误'
            ];
            
        } catch (PDOException $e) {
            $this->logger->logLogin(null, $username, 0, '登录失败: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => '登录失败，请重试'
            ];
        }
    }
    
    // 用户退出
    public function logout() {
        $userId = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['username'] ?? '';
        
        // 记录退出日志
        if ($userId) {
            $this->logger->logLogout($userId, $username);
        }
        
        // 清除所有会话变量
        $_SESSION = array();
        
        // 删除会话cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // 销毁会话
        session_destroy();
        
        return [
            'success' => true,
            'message' => '退出成功'
        ];
    }
    
    // 检查是否已登录
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    // 获取当前用户信息
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return [
                'success' => false,
                'message' => '用户未登录'
            ];
        }
        
        // 检查密码是否为初始密码
        $isInitialPassword = $this->checkInitialPassword($_SESSION['user_id']);
        
        // 获取用户头像
        $avatar = $this->getUserAvatar($_SESSION['user_id']);
        if (!$avatar || !file_exists(__DIR__ . '/../' . $avatar)) {
            $avatar = 'image/logo.png'; // 默认头像
        }
        
        return [
            'success' => true,
            'user' => [
                'F_id' => $_SESSION['user_id'],
                'F_username' => $_SESSION['username'],
                'F_realname' => $_SESSION['realname'] ?? $_SESSION['username'],
                'F_role' => $_SESSION['role'],
                'F_avatar' => $avatar,
                'is_initial_password' => $isInitialPassword
            ]
        ];
    }
    
    // 获取用户头像
    private function getUserAvatar($userId) {
        try {
            $query = "SELECT F_avatar FROM users WHERE F_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                return $result['F_avatar'];
            }
            
            return null;
        } catch (PDOException $e) {
            error_log("获取用户头像失败: " . $e->getMessage());
            return null;
        }
    }
    
    // 检查密码是否为初始密码
    private function checkInitialPassword($userId) {
        try {
            $query = "SELECT F_password FROM users WHERE F_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                // 检查密码是否为初始密码 "000000"
                return password_verify('000000', $user['F_password']);
            }
            
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // 获取当前用户ID
    public function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
}

// 处理认证请求（只在直接访问Auth.php时生效）
if (isset($_GET['action']) && basename($_SERVER['PHP_SELF']) === 'Auth.php') {
    $auth = new Auth();
    $response = [];
    
    switch ($_GET['action']) {
        case 'login':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // 检查CSRF Token
                if (!SecurityConfig::validateCSRFToken($_POST['csrf_token'] ?? '')) {
                    $response = [
                        'success' => false,
                        'message' => '安全验证失败，请刷新页面重试'
                    ];
                } else {
                    $username = $_POST['username'] ?? '';
                    $password = $_POST['password'] ?? '';
                    $response = $auth->login($username, $password);
                }
            }
            break;
            
        case 'logout':
            $response = $auth->logout();
            break;
            
        case 'getCurrentUser':
            $response = $auth->getCurrentUser();
            break;
            
        default:
            $response = [
                'success' => false,
                'message' => '未知操作'
            ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>