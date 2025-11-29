<?php
// get_invoice_detail.php - 获取发票详细信息
session_start();
require_once 'config.php';
require_once 'Auth.php';

// 检查用户是否登录
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('请求方法不正确');
    }
    
    $invoiceId = $_GET['id'] ?? '';
    
    if (empty($invoiceId)) {
        throw new Exception('发票ID不能为空');
    }
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // 查询发票详细信息
    $query = "
        SELECT 
            i.F_Id, 
            i.F_inv_code, 
            i.F_inv_num, 
            i.F_inv_date, 
            i.F_inv_money, 
            i.F_inv_user, 
            i.F_inv_doc, 
            i.F_inv_qr, 
            i.F_creator_id,
            i.F_CreatorTime,
            u.F_username,
            u.F_realname
        FROM invoice_info i
        LEFT JOIN users u ON i.F_creator_id = u.F_id
        WHERE i.F_Id = :id
        LIMIT 1
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $invoiceId);
    $stmt->execute();
    
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$invoice) {
        throw new Exception('发票记录不存在');
    }
    
    echo json_encode([
        'success' => true,
        'invoice' => $invoice
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>