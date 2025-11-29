<?php
// get_invoices.php - 获取发票数据
// 开启输出缓冲，防止意外输出污染JSON响应
ob_start();

require_once 'SecurityConfig.php';
require_once 'config.php';
require_once 'Auth.php';

// 配置安全的Session
if (!SecurityConfig::configureSession()) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Session验证失败']);
    ob_end_flush();
    exit;
}

// 检查用户是否登录
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '请先登录']);
    ob_end_flush();
    exit;
}

header('Content-Type: application/json');

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // 获取分页参数
    $page = isset($_GET['page']) ? max(1, SecurityConfig::sanitizeInput($_GET['page'], 'int')) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(100, SecurityConfig::sanitizeInput($_GET['limit'], 'int'))) : 5;
    $offset = ($page - 1) * $limit;
    
    // 获取搜索参数并验证
    $invoiceNumber = SecurityConfig::sanitizeInput($_GET['invoiceNumber'] ?? '', 'string');
    $invoiceUser = SecurityConfig::sanitizeInput($_GET['invoiceUser'] ?? '', 'string');
    $creator = SecurityConfig::sanitizeInput($_GET['creator'] ?? '', 'string');
    $startDate = SecurityConfig::sanitizeInput($_GET['startDate'] ?? '', 'string');
    $endDate = SecurityConfig::sanitizeInput($_GET['endDate'] ?? '', 'string');
    
    // 验证日期格式
    if ($startDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => '开始日期格式不正确']);
        ob_end_flush();
        exit;
    }
    
    if ($endDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => '结束日期格式不正确']);
        ob_end_flush();
        exit;
    }
    
    // 构建WHERE条件
    $whereConditions = [];
    $params = [];
    
    if ($invoiceNumber) {
        $whereConditions[] = "i.F_inv_num LIKE :invoiceNumber";
        $params[':invoiceNumber'] = '%' . $invoiceNumber . '%';
    }
    
    if ($invoiceUser) {
        $whereConditions[] = "i.F_inv_user LIKE :invoiceUser";
        $params[':invoiceUser'] = '%' . $invoiceUser . '%';
    }
    
    if ($creator) {
        $whereConditions[] = "i.F_creator_id = :creator";
        $params[':creator'] = $creator;
    }
    
    if ($startDate) {
        $whereConditions[] = "DATE(i.F_CreatorTime) >= :startDate";
        $params[':startDate'] = $startDate;
    }
    
    if ($endDate) {
        $whereConditions[] = "DATE(i.F_CreatorTime) <= :endDate";
        $params[':endDate'] = $endDate;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // 查询总记录数
    $countQuery = "
        SELECT COUNT(*) as total
        FROM invoice_info i
        LEFT JOIN users u ON i.F_creator_id = u.F_id
        $whereClause
    ";
    
    $countStmt = $conn->prepare($countQuery);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 查询发票数据，联表查询获取录入人信息
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
        $whereClause
        ORDER BY i.F_CreatorTime DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalPages = ceil($totalRecords / $limit);
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'invoices' => $invoices,
        'pagination' => [
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalRecords' => $totalRecords,
            'limit' => $limit,
            'hasNextPage' => $page < $totalPages,
            'hasPrevPage' => $page > 1
        ]
    ]);
    ob_end_flush();
    
} catch (PDOException $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => '获取发票数据失败: 数据库错误'
    ]);
    ob_end_flush();
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => '获取发票数据失败: ' . $e->getMessage()
    ]);
    ob_end_flush();
}
?>