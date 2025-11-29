<?php
/**
 * 用户姓名修改API
 */

require_once 'Auth.php';
require_once 'SecurityConfig.php';
require_once 'UserManager.php';

header('Content-Type: application/json; charset=utf-8');

// 检查用户是否登录
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => '未登录'
    ]);
    exit;
}

// 验证CSRF Token
if (!isset($_POST['csrf_token']) || !SecurityConfig::validateCSRFToken($_POST['csrf_token'])) {
    echo json_encode([
        'success' => false,
        'message' => 'CSRF验证失败'
    ]);
    exit;
}

// 获取参数
$realname = isset($_POST['realname']) ? trim($_POST['realname']) : '';

// 验证姓名
if (empty($realname)) {
    echo json_encode([
        'success' => false,
        'message' => '姓名不能为空'
    ]);
    exit;
}

if (mb_strlen($realname) > 50) {
    echo json_encode([
        'success' => false,
        'message' => '姓名长度不能超过50个字符'
    ]);
    exit;
}

// 获取当前用户ID
$userId = $_SESSION['user_id'];

// 更新用户姓名
$userManager = new UserManager();
$result = $userManager->updateUserRealname($userId, $realname);

echo json_encode($result);
?>

