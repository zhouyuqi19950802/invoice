<?php
// delete_invoice.php - 删除发票
require_once 'SecurityConfig.php';
require_once 'Auth.php';

// 配置安全的Session
if (!SecurityConfig::configureSession()) {
    echo json_encode(['success' => false, 'message' => 'Session验证失败']);
    exit;
}

// 检查用户是否登录
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('请求方法不正确');
    }
    
    // 检查CSRF Token
    if (!SecurityConfig::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        throw new Exception('安全验证失败，请刷新页面重试');
    }
    
    $invoiceId = SecurityConfig::sanitizeInput($_POST['invoice_id'] ?? '', 'int');
    
    if (!$invoiceId) {
        throw new Exception('发票ID不能为空');
    }
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // 删除发票
    $query = "DELETE FROM invoice_info WHERE F_Id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $invoiceId);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => '发票删除成功'
        ]);
    } else {
        throw new Exception('删除失败');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>