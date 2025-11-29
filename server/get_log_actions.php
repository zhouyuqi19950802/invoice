<?php
// get_log_actions.php - 获取日志操作类型
session_start();
require_once 'Auth.php';
require_once 'Logger.php';

// 检查用户权限
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '用户未登录']);
    exit;
}

$user = $auth->getCurrentUser();
if ($user['user']['F_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '权限不足']);
    exit;
}

$logger = new Logger();
$actions = $logger->getActionTypes();

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'data' => $actions
]);
?>