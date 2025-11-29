<?php
// 抑制错误输出，确保返回JSON格式
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 开启输出缓冲，捕获任何意外输出
ob_start();

require_once 'Auth.php';
require_once 'ConfigManager.php';
require_once 'SecurityConfig.php';
require_once 'Logger.php';

// 清除输出缓冲中的任何内容
ob_clean();

header('Content-Type: application/json; charset=utf-8');

// 辅助函数：输出JSON并退出
function outputJsonAndExit($data) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

// 检查用户是否登录
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    outputJsonAndExit([
        'success' => false,
        'message' => '未登录'
    ]);
}

// 检查管理员权限
$user = $auth->getCurrentUser();
$role = isset($user['user']['F_role']) ? $user['user']['F_role'] : 'user';

if ($role !== 'admin') {
    outputJsonAndExit([
        'success' => false,
        'message' => '权限不足'
    ]);
}

// 验证CSRF Token
if (!isset($_POST['csrf_token']) || !SecurityConfig::validateCSRFToken($_POST['csrf_token'])) {
    outputJsonAndExit([
        'success' => false,
        'message' => 'CSRF验证失败'
    ]);
}

// 获取配置数据
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    outputJsonAndExit([
        'success' => false,
        'message' => '请求方法错误'
    ]);
}

$configs = [];
$allowedKeys = ['site_favicon', 'login_logo', 'login_title', 'login_description', 'main_logo', 'main_title_text'];

foreach ($allowedKeys as $key) {
    if (isset($_POST[$key])) {
        $configs[$key] = trim($_POST[$key]);
    }
}

if (empty($configs)) {
    outputJsonAndExit([
        'success' => false,
        'message' => '没有配置项需要保存'
    ]);
}

try {
    // 保存配置
    $configManager = new ConfigManager();
    $result = $configManager->saveConfigs($configs);
    
    // 记录日志
    if ($result['success']) {
        $logger = new Logger();
        $logger->log('CONFIG_UPDATE', '更新系统配置', true, null, null, [
            'updated_keys' => array_keys($configs)
        ]);
    }
    
    // 输出结果
    outputJsonAndExit($result);
} catch (Exception $e) {
    // 捕获所有异常，返回JSON格式的错误信息
    error_log("保存配置异常: " . $e->getMessage());
    outputJsonAndExit([
        'success' => false,
        'message' => '保存配置时发生错误: ' . $e->getMessage()
    ]);
}

