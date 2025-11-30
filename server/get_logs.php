<?php
// get_logs.php - 获取系统日志
// 开启输出缓冲，防止意外输出污染JSON响应
ob_start();

session_start();
require_once 'Auth.php';
require_once 'Logger.php';

// 检查用户权限
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    ob_clean();
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '用户未登录']);
    ob_end_flush();
    exit;
}

$user = $auth->getCurrentUser();
if ($user['user']['F_role'] !== 'admin') {
    ob_clean();
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '权限不足']);
    ob_end_flush();
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

try {
    $result = $logger->getLogs($page, $limit, $filters);
    
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $result
    ]);
    ob_end_flush();
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    error_log("Get logs API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '获取日志失败: ' . $e->getMessage()
    ]);
    ob_end_flush();
}
?>