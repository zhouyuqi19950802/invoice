<?php
require_once 'Auth.php';
require_once 'SecurityConfig.php';
require_once 'UserManager.php';
require_once 'Logger.php';

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

// 检查是否有文件上传
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'success' => false,
        'message' => '没有上传文件或上传失败'
    ]);
    exit;
}

$file = $_FILES['avatar'];
$userId = $_SESSION['user_id'];

// 验证文件类型
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$fileType = mime_content_type($file['tmp_name']);

if (!in_array($fileType, $allowedTypes)) {
    echo json_encode([
        'success' => false,
        'message' => '不支持的文件类型，仅支持JPG、PNG、GIF格式'
    ]);
    exit;
}

// 验证文件大小（最大2MB）
$maxSize = 2 * 1024 * 1024; // 2MB
if ($file['size'] > $maxSize) {
    echo json_encode([
        'success' => false,
        'message' => '文件大小不能超过2MB'
    ]);
    exit;
}

// 创建上传目录
$uploadDir = __DIR__ . '/../uploads/avatars/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// 生成唯一文件名
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$fileName = 'avatar_' . $userId . '_' . time() . '.' . $extension;
$filePath = $uploadDir . $fileName;

// 移动文件
if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    echo json_encode([
        'success' => false,
        'message' => '文件上传失败'
    ]);
    exit;
}

// 保存头像路径到数据库
$userManager = new UserManager();
$relativePath = 'uploads/avatars/' . $fileName;

// 获取旧头像路径并删除
$oldAvatar = $userManager->getUserAvatar($userId);
if ($oldAvatar && file_exists(__DIR__ . '/../' . $oldAvatar)) {
    @unlink(__DIR__ . '/../' . $oldAvatar);
}

$result = $userManager->updateUserAvatar($userId, $relativePath);

// 记录日志
if ($result['success']) {
    $logger = new Logger();
    $logger->log('USER_AVATAR_UPDATE', '更新用户头像', true, null, null);
}

echo json_encode([
    'success' => true,
    'message' => '头像上传成功',
    'avatar_path' => $relativePath
]);

