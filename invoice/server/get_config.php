<?php
require_once 'Auth.php';
require_once 'ConfigManager.php';
require_once 'SecurityConfig.php';

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

// 获取当前用户信息
$user = $auth->getCurrentUser();
$role = isset($user['user']['F_role']) ? $user['user']['F_role'] : 'user';

// 只有管理员可以获取所有配置
$configManager = new ConfigManager();

// 获取所有配置
$result = $configManager->getAllConfigs();

echo json_encode($result);

