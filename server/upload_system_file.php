<?php
/**
 * 系统文件上传API（用于上传logo、favicon等系统配置图片）
 */

require_once 'Auth.php';
require_once 'SecurityConfig.php';
require_once 'ConfigManager.php';
require_once 'Logger.php';

header('Content-Type: application/json; charset=utf-8');

// 检查用户是否登录且为管理员
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => '未登录'
    ]);
    exit;
}

$user = $auth->getCurrentUser();
$role = isset($user['user']['F_role']) ? $user['user']['F_role'] : 'user';
if ($role !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => '只有管理员可以上传系统文件'
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

// 获取文件类型参数
$fileType = isset($_POST['file_type']) ? $_POST['file_type'] : '';
$allowedTypes = ['favicon', 'logo', 'login_logo', 'main_logo'];

if (!in_array($fileType, $allowedTypes)) {
    echo json_encode([
        'success' => false,
        'message' => '无效的文件类型参数'
    ]);
    exit;
}

// 检查是否有文件上传
$fileFieldName = 'system_file';
if (!isset($_FILES[$fileFieldName]) || $_FILES[$fileFieldName]['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'success' => false,
        'message' => '没有上传文件或上传失败'
    ]);
    exit;
}

$file = $_FILES[$fileFieldName];

// 根据文件类型设置验证规则
$allowedExtensions = [];
$allowedMimeTypes = [];
$maxSize = 0;
$uploadDir = '';
$configKey = '';

switch ($fileType) {
    case 'favicon':
        $allowedExtensions = ['ico', 'png', 'jpg', 'jpeg'];
        $allowedMimeTypes = ['image/x-icon', 'image/png', 'image/jpeg'];
        $maxSize = 500 * 1024; // 500KB
        $uploadDir = __DIR__ . '/../uploads/system/favicon/';
        $configKey = 'site_favicon';
        break;
    case 'logo':
    case 'login_logo':
        $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif', 'svg'];
        $allowedMimeTypes = ['image/png', 'image/jpeg', 'image/gif', 'image/svg+xml'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        $uploadDir = __DIR__ . '/../uploads/system/logo/';
        $configKey = $fileType === 'login_logo' ? 'login_logo' : 'main_logo';
        break;
    case 'main_logo':
        $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif', 'svg'];
        $allowedMimeTypes = ['image/png', 'image/jpeg', 'image/gif', 'image/svg+xml'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        $uploadDir = __DIR__ . '/../uploads/system/logo/';
        $configKey = 'main_logo';
        break;
}

// 验证文件扩展名
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($extension, $allowedExtensions)) {
    echo json_encode([
        'success' => false,
        'message' => '不支持的文件格式。允许的格式：' . implode(', ', $allowedExtensions)
    ]);
    exit;
}

// 验证文件大小
if ($file['size'] > $maxSize) {
    $maxSizeMB = round($maxSize / 1024 / 1024, 2);
    echo json_encode([
        'success' => false,
        'message' => '文件大小超过限制（最大' . $maxSizeMB . 'MB）'
    ]);
    exit;
}

// 验证MIME类型
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedMimeTypes)) {
    echo json_encode([
        'success' => false,
        'message' => '文件类型验证失败'
    ]);
    exit;
}

// 创建上传目录
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// 生成唯一文件名
$fileName = $fileType . '_' . time() . '_' . uniqid() . '.' . $extension;
$filePath = $uploadDir . $fileName;

// 移动文件
if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    echo json_encode([
        'success' => false,
        'message' => '文件上传失败'
    ]);
    exit;
}

// 根据文件类型构建相对路径
$relativePath = '';
switch ($fileType) {
    case 'favicon':
        $relativePath = 'uploads/system/favicon/' . $fileName;
        break;
    case 'logo':
    case 'login_logo':
    case 'main_logo':
        $relativePath = 'uploads/system/logo/' . $fileName;
        break;
}

// 获取旧的配置值并删除旧文件
$configManager = new ConfigManager();
$oldPath = $configManager->getConfig($configKey, '');
if ($oldPath && file_exists(__DIR__ . '/../' . $oldPath)) {
    // 检查是否是默认路径（image/目录下的文件不删除，只删除uploads目录下的文件）
    if (strpos($oldPath, 'uploads/') === 0) {
        @unlink(__DIR__ . '/../' . $oldPath);
    }
}

// 更新配置
$result = $configManager->setConfig($configKey, $relativePath);

// 记录日志
if ($result) {
    $logger = new Logger();
    $logger->log('SYSTEM_CONFIG_UPDATE', "上传系统文件: {$fileType}", true, null, null);
}

echo json_encode([
    'success' => true,
    'message' => '文件上传成功',
    'file_path' => $relativePath,
    'config_key' => $configKey
]);
?>

