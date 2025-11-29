<?php
// process_invoice.php
require_once 'SecurityConfig.php';
require_once 'Auth.php';
require_once 'InvoiceProcessor.php';
require_once 'Logger.php';

// 配置安全的Session
if (!SecurityConfig::configureSession()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Session验证失败']);
    exit;
}

// 检查用户是否登录
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

// 检查CSRF Token
if (!SecurityConfig::validateCSRFToken($_POST['csrf_token'] ?? '')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '安全验证失败，请刷新页面重试']);
    exit;
}

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('请求方法不正确');
    }

    $qrCode = $_POST['qr_code'] ?? '';
    $invUser = $_POST['inv_user'] ?? '';
    $invDoc = $_POST['inv_doc'] ?? '';
    
    if (empty($qrCode)) {
        throw new Exception('二维码内容不能为空');
    }
    
    if (empty($invUser)) {
        throw new Exception('凭证使用人不能为空');
    }
    
    if (empty($invDoc)) {
        throw new Exception('凭证号不能为空');
    }

    $logger = new Logger();
    $currentUser = $auth->getCurrentUser();
    $userId = $currentUser['user']['F_id'];
    $username = $currentUser['user']['F_username'];
    
    $processor = new InvoiceProcessor();
    
    // 检查是否重复
    $existing = $processor->checkDuplicate($qrCode);
    if ($existing) {
        $logger->logInvoice($userId, $username, 'INVOICE_DUPLICATE', $existing['F_id'] ?? null, '发票重复检测', 1);
        $response = [
            'success' => true,
            'duplicate' => true,
            'existing_record' => $existing,
            'message' => '发票重复'
        ];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    // 解析二维码
    $invoiceData = $processor->parseQRCode($qrCode);
    if (isset($invoiceData['error'])) {
        $logger->logInvoice($userId, $username, 'INVOICE_PARSE_ERROR', null, '二维码解析失败: ' . $invoiceData['error'], 0, $invoiceData['error']);
        throw new Exception($invoiceData['error']);
    }

    // 添加用户和凭证信息，以及当前用户ID
    $invoiceData['F_inv_user'] = $invUser;
    $invoiceData['F_inv_doc'] = $invDoc;
    $invoiceData['F_creator_id'] = $auth->getCurrentUserId();

    // 保存发票信息
    $saved = $processor->saveInvoice($invoiceData);
    if (!$saved) {
        $logger->logInvoice($userId, $username, 'INVOICE_SAVE_ERROR', null, '发票保存失败', 0, '发票保存失败');
        throw new Exception('发票保存失败');
    }

    $logger->logInvoice($userId, $username, 'INVOICE_CREATE', $saved, '新增发票: ' . $invoiceData['F_inv_code'], 1);
    
    $response = [
        'success' => true,
        'duplicate' => false,
        'invoice_data' => $invoiceData,
        'message' => '发票录入成功'
    ];

} catch (Exception $e) {
    if (isset($logger) && isset($userId) && isset($username)) {
        $logger->logInvoice($userId ?? null, $username ?? '', 'INVOICE_PROCESS_ERROR', null, '发票处理异常', 0, $e->getMessage());
    }
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>