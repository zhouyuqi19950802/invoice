<?php
// get_logs.php - 获取系统日志
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

// 获取请求参数
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$filters = [];

if (!empty($_GET['action'])) {
    $filters['action'] = $_GET['action'];
}
if (!empty($_GET['username'])) {
    $filters['username'] = $_GET['username'];
}
if (!empty($_GET['ip_address'])) {
    $filters['ip_address'] = $_GET['ip_address'];
}
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $filters['status'] = (int)$_GET['status'];
}


$result = $logger->getLogs($page, $limit, $filters);

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'data' => $result
]);
?>