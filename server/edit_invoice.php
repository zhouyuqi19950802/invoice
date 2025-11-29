<?php
// edit_invoice.php - 编辑发票信息
session_start();
require_once 'config.php';
require_once 'Auth.php';
require_once 'SecurityConfig.php';

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
    
    // 验证CSRF令牌
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        throw new Exception('CSRF令牌验证失败');
    }
    
    $invoiceId = SecurityConfig::sanitizeInput($_POST['invoice_id'], 'int');
    $invUser = SecurityConfig::sanitizeInput($_POST['inv_user'], 'string');
    $invDoc = SecurityConfig::sanitizeInput($_POST['inv_doc'], 'string');
    
    if (empty($invoiceId)) {
        throw new Exception('发票ID不能为空');
    }
    
    if (empty($invUser)) {
        throw new Exception('凭证使用人不能为空');
    }
    
    if (empty($invDoc)) {
        throw new Exception('凭证号不能为空');
    }
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // 检查发票是否存在
    $checkQuery = "SELECT F_Id FROM invoice_info WHERE F_Id = :id";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindParam(':id', $invoiceId);
    $checkStmt->execute();
    
    if (!$checkStmt->fetch()) {
        throw new Exception('发票记录不存在');
    }
    
    // 更新发票信息
    $updateQuery = "
        UPDATE invoice_info 
        SET F_inv_user = :inv_user, 
            F_inv_doc = :inv_doc
        WHERE F_Id = :id
    ";
    
    $stmt = $conn->prepare($updateQuery);
    $stmt->bindParam(':inv_user', $invUser);
    $stmt->bindParam(':inv_doc', $invDoc);
    $stmt->bindParam(':id', $invoiceId);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => '发票信息更新成功'
        ]);
    } else {
        throw new Exception('更新发票信息失败');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>