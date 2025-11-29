<?php
// UserManager.php - 用户管理类
session_start();
require_once 'config.php';
require_once 'Logger.php';

class UserManager {
    private $conn;
    private $logger;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->logger = new Logger();
    }
    
    // 获取所有用户
    public function getUsers() {
        try {
            $query = "SELECT F_id, F_username, F_realname, F_role, F_status, F_create_time 
                      FROM users 
                      ORDER BY F_create_time DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'users' => $users
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => '获取用户列表失败: ' . $e->getMessage()
            ];
        }
    }
    
    // 新增用户
    public function addUser($username, $realname, $password, $role = 'user') {
        try {
            $currentUserId = $_SESSION['user_id'] ?? null;
            $currentUsername = $_SESSION['username'] ?? '';
            
            // 检查用户名是否已存在
            $checkQuery = "SELECT F_id FROM users WHERE F_username = :username";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':username', $username);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                $this->logger->logUser($currentUserId, $currentUsername, 'USER_CREATE', null, "新增用户失败: 用户名已存在 - $username", 0, '用户名已存在');
                return [
                    'success' => false,
                    'message' => '用户名已存在'
                ];
            }
            
            // 插入新用户
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $status = 1; // 1表示启用状态
            $insertQuery = "INSERT INTO users (F_username, F_realname, F_password, F_role, F_status, F_create_time) 
                           VALUES (:username, :realname, :password, :role, :status, NOW())";
            
            $insertStmt = $this->conn->prepare($insertQuery);
            $insertStmt->bindParam(':username', $username);
            $insertStmt->bindParam(':realname', $realname);
            $insertStmt->bindParam(':password', $hashedPassword);
            $insertStmt->bindParam(':role', $role);
            $insertStmt->bindParam(':status', $status);
            
            if ($insertStmt->execute()) {
                $newUserId = $this->conn->lastInsertId();
                $this->logger->logUser($currentUserId, $currentUsername, 'USER_CREATE', $newUserId, "新增用户: $username ($realname)", 1);
                return [
                    'success' => true,
                    'message' => '用户添加成功'
                ];
            } else {
                $this->logger->logUser($currentUserId, $currentUsername, 'USER_CREATE', null, "新增用户失败: $username", 0, '数据库执行失败');
                return [
                    'success' => false,
                    'message' => '用户添加失败'
                ];
            }
            
        } catch (PDOException $e) {
            $this->logger->logUser($currentUserId ?? null, $currentUsername ?? '', 'USER_CREATE', null, "新增用户异常: $username", 0, $e->getMessage());
            return [
                'success' => false,
                'message' => '添加用户失败: ' . $e->getMessage()
            ];
        }
    }
    
    // 删除用户
    public function deleteUser($userId) {
        try {
            // 检查是否为当前登录用户
            if (isset($_SESSION['user_id']) && $userId == $_SESSION['user_id']) {
                return [
                    'success' => false,
                    'message' => '不能删除当前登录用户'
                ];
            }
            
            // 检查用户是否存在
            $checkQuery = "SELECT F_username FROM users WHERE F_id = :user_id";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':user_id', $userId);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => '用户不存在'
                ];
            }
            
            // 删除用户
            $deleteQuery = "DELETE FROM users WHERE F_id = :user_id";
            $deleteStmt = $this->conn->prepare($deleteQuery);
            $deleteStmt->bindParam(':user_id', $userId);
            
            if ($deleteStmt->execute()) {
                return [
                    'success' => true,
                    'message' => '用户删除成功'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => '用户删除失败'
                ];
            }
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => '删除用户失败: ' . $e->getMessage()
            ];
        }
    }
    
    // 修改用户状态
    public function changeUserStatus($userId, $status) {
        try {
            // 检查用户是否存在
            $checkQuery = "SELECT F_username FROM users WHERE F_id = :user_id";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':user_id', $userId);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => '用户不存在'
                ];
            }
            
            // 检查是否为当前登录用户
            if (isset($_SESSION['user_id']) && $userId == $_SESSION['user_id']) {
                return [
                    'success' => false,
                    'message' => '不能修改当前登录用户状态'
                ];
            }
            
            // 更新用户状态
            $updateQuery = "UPDATE users SET F_status = :status WHERE F_id = :user_id";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':status', $status);
            $updateStmt->bindParam(':user_id', $userId);
            
            if ($updateStmt->execute()) {
                return [
                    'success' => true,
                    'message' => '用户状态修改成功'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => '用户状态修改失败'
                ];
            }
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => '修改用户状态失败: ' . $e->getMessage()
            ];
        }
    }
    
    // 修改用户角色
    public function changeUserRole($userId, $role) {
        try {
            // 检查用户是否存在
            $checkQuery = "SELECT F_username FROM users WHERE F_id = :user_id";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':user_id', $userId);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => '用户不存在'
                ];
            }
            
            // 检查是否为当前登录用户
            if (isset($_SESSION['user_id']) && $userId == $_SESSION['user_id']) {
                return [
                    'success' => false,
                    'message' => '不能修改当前登录用户角色'
                ];
            }
            
            // 更新用户角色
            $updateQuery = "UPDATE users SET F_role = :role WHERE F_id = :user_id";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':role', $role);
            $updateStmt->bindParam(':user_id', $userId);
            
            if ($updateStmt->execute()) {
                return [
                    'success' => true,
                    'message' => '用户角色修改成功'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => '用户角色修改失败'
                ];
            }
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => '修改用户角色失败: ' . $e->getMessage()
            ];
        }
    }
    
    // 修改用户密码（管理员功能）
    public function changeUserPassword($userId, $newPassword) {
        try {
            // 检查用户是否存在
            $checkQuery = "SELECT F_username FROM users WHERE F_id = :user_id";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':user_id', $userId);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => '用户不存在'
                ];
            }
            
            // 更新密码
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateQuery = "UPDATE users SET F_password = :password WHERE F_id = :user_id";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':password', $hashedPassword);
            $updateStmt->bindParam(':user_id', $userId);
            
            if ($updateStmt->execute()) {
                return [
                    'success' => true,
                    'message' => '密码修改成功'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => '密码修改失败'
                ];
            }
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => '修改密码失败: ' . $e->getMessage()
            ];
        }
    }
    
    // 修改密码
    public function changePassword($currentPassword, $newPassword) {
        if (!isset($_SESSION['user_id'])) {
            return [
                'success' => false,
                'message' => '用户未登录'
            ];
        }
        
        try {
            $userId = $_SESSION['user_id'];
            
            // 验证当前密码
            $query = "SELECT F_password FROM users WHERE F_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!password_verify($currentPassword, $user['F_password'])) {
                    return [
                        'success' => false,
                        'message' => '当前密码错误'
                    ];
                }
                
                // 更新密码
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateQuery = "UPDATE users SET F_password = :password WHERE F_id = :user_id";
                $updateStmt = $this->conn->prepare($updateQuery);
                $updateStmt->bindParam(':password', $hashedPassword);
                $updateStmt->bindParam(':user_id', $userId);
                
                if ($updateStmt->execute()) {
                    return [
                        'success' => true,
                        'message' => '密码修改成功'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => '密码修改失败'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => '用户不存在'
                ];
            }
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => '修改密码失败: ' . $e->getMessage()
            ];
        }
    }
    
    // 更新用户头像
    public function updateUserAvatar($userId, $avatarPath) {
        try {
            $updateQuery = "UPDATE users SET F_avatar = :avatar WHERE F_id = :user_id";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':avatar', $avatarPath);
            $updateStmt->bindParam(':user_id', $userId);
            
            if ($updateStmt->execute()) {
                return [
                    'success' => true,
                    'message' => '头像更新成功'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => '头像更新失败'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => '更新头像失败: ' . $e->getMessage()
            ];
        }
    }
    
    // 获取用户头像路径
    public function getUserAvatar($userId) {
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
    
    // 更新用户姓名（用户自己修改）
    public function updateUserRealname($userId, $realname) {
        try {
            // 验证姓名长度
            if (empty(trim($realname))) {
                return [
                    'success' => false,
                    'message' => '姓名不能为空'
                ];
            }
            
            if (mb_strlen(trim($realname)) > 50) {
                return [
                    'success' => false,
                    'message' => '姓名长度不能超过50个字符'
                ];
            }
            
            // 检查用户是否存在
            $checkQuery = "SELECT F_id, F_realname FROM users WHERE F_id = :user_id";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':user_id', $userId);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => '用户不存在'
                ];
            }
            
            $user = $checkStmt->fetch(PDO::FETCH_ASSOC);
            $oldRealname = $user['F_realname'];
            
            // 更新用户姓名
            $realname = trim($realname);
            $updateQuery = "UPDATE users SET F_realname = :realname WHERE F_id = :user_id";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':realname', $realname);
            $updateStmt->bindParam(':user_id', $userId);
            
            if ($updateStmt->execute()) {
                // 如果修改成功，更新session中的realname
                if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
                    $_SESSION['realname'] = $realname;
                }
                
                // 记录日志
                $this->logger->log('USER_REALNAME_UPDATE', "修改用户姓名: {$oldRealname} -> {$realname}", true, null, null);
                
                return [
                    'success' => true,
                    'message' => '姓名修改成功',
                    'realname' => $realname
                ];
            } else {
                return [
                    'success' => false,
                    'message' => '姓名修改失败'
                ];
            }
        } catch (PDOException $e) {
            error_log("修改用户姓名失败: " . $e->getMessage());
            return [
                'success' => false,
                'message' => '修改用户姓名失败: ' . $e->getMessage()
            ];
        }
    }
}

// 检查管理员权限
function checkAdminPermission() {
    if (!isset($_SESSION['user_id'])) {
        return ['success' => false, 'message' => '用户未登录'];
    }
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "SELECT F_role FROM users WHERE F_id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || $user['F_role'] !== 'admin') {
            return ['success' => false, 'message' => '权限不足，需要管理员权限'];
        }
        
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => '权限检查失败: ' . $e->getMessage()];
    }
}

// 处理用户管理请求
if (isset($_GET['action'])) {
    $userManager = new UserManager();
    $response = [];
    
    switch ($_GET['action']) {
        case 'getUsers':
            // 所有登录用户都可以查看用户列表（用于筛选功能）
            if (!isset($_SESSION['user_id'])) {
                $response = ['success' => false, 'message' => '用户未登录'];
                break;
            }
            $response = $userManager->getUsers();
            break;
            
        case 'addUser':
            // 需要管理员权限
            $permissionCheck = checkAdminPermission();
            if (!$permissionCheck['success']) {
                $response = $permissionCheck;
                break;
            }
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $username = $_POST['username'] ?? '';
                $realname = $_POST['realname'] ?? '';
                $password = $_POST['password'] ?? '';
                $role = $_POST['role'] ?? 'user';
                $response = $userManager->addUser($username, $realname, $password, $role);
            }
            break;
            
        case 'deleteUser':
            // 需要管理员权限
            $permissionCheck = checkAdminPermission();
            if (!$permissionCheck['success']) {
                $response = $permissionCheck;
                break;
            }
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $userId = $_POST['user_id'] ?? '';
                $response = $userManager->deleteUser($userId);
            }
            break;
            
        case 'changeUserPassword':
            // 需要管理员权限
            $permissionCheck = checkAdminPermission();
            if (!$permissionCheck['success']) {
                $response = $permissionCheck;
                break;
            }
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $userId = $_POST['user_id'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $response = $userManager->changeUserPassword($userId, $newPassword);
            }
            break;
            
        case 'changePassword':
            // 用户自己修改密码，不需要管理员权限
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $currentPassword = $_POST['current_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $response = $userManager->changePassword($currentPassword, $newPassword);
            }
            break;
            
        case 'changeUserStatus':
            // 需要管理员权限
            $permissionCheck = checkAdminPermission();
            if (!$permissionCheck['success']) {
                $response = $permissionCheck;
                break;
            }
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $userId = $_POST['user_id'] ?? '';
                $status = $_POST['status'] ?? '';
                $response = $userManager->changeUserStatus($userId, $status);
            }
            break;
            
        case 'changeUserRole':
            // 需要管理员权限
            $permissionCheck = checkAdminPermission();
            if (!$permissionCheck['success']) {
                $response = $permissionCheck;
                break;
            }
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $userId = $_POST['user_id'] ?? '';
                $role = $_POST['role'] ?? '';
                $response = $userManager->changeUserRole($userId, $role);
            }
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