<?php
// export_invoices.php - 导出发票数据
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
    $database = new Database();
    $conn = $database->getConnection();
    
    // 获取搜索参数
    $invoiceNumber = isset($_GET['invoiceNumber']) ? trim($_GET['invoiceNumber']) : '';
    $invoiceUser = isset($_GET['invoiceUser']) ? trim($_GET['invoiceUser']) : '';
    $creator = isset($_GET['creator']) ? trim($_GET['creator']) : '';
    $startDate = isset($_GET['startDate']) ? trim($_GET['startDate']) : '';
    $endDate = isset($_GET['endDate']) ? trim($_GET['endDate']) : '';
    $exportType = isset($_GET['type']) ? trim($_GET['type']) : 'csv';
    
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
    
    // 查询发票数据
    $query = "
        SELECT 
            i.F_Id, 
            i.F_inv_code, 
            i.F_inv_num, 
            i.F_inv_date, 
            i.F_inv_money, 
            i.F_inv_user, 
            i.F_inv_doc, 
            i.F_CreatorTime,
            u.F_username,
            u.F_realname
        FROM invoice_info i
        LEFT JOIN users u ON i.F_creator_id = u.F_id
        $whereClause
        ORDER BY i.F_CreatorTime DESC
    ";
    
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($invoices)) {
        echo json_encode(['success' => false, 'message' => '没有可导出的数据']);
        exit;
    }
    
    // 设置文件名
    $filename = '发票数据导出_' . date('Y-m-d_H-i-s');
    
    if ($exportType === 'excel') {
        // 导出Excel格式
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
        header('Cache-Control: max-age=0');
        
        // 输出HTML表格格式的Excel
        echo '<table border="1">';
        echo '<tr>';
        echo '<th>发票代码</th>';
        echo '<th>发票号码</th>';
        echo '<th>开票日期</th>';
        echo '<th>发票金额</th>';
        echo '<th>使用人</th>';
        echo '<th>凭证号</th>';
        echo '<th>录入人</th>';
        echo '<th>录入时间</th>';
        echo '</tr>';
        
        foreach ($invoices as $invoice) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($invoice['F_inv_code']) . '</td>';
            echo '<td>' . htmlspecialchars($invoice['F_inv_num']) . '</td>';
            echo '<td>' . htmlspecialchars($invoice['F_inv_date']) . '</td>';
            echo '<td>¥' . number_format($invoice['F_inv_money'], 2) . '</td>';
            echo '<td>' . htmlspecialchars($invoice['F_inv_user']) . '</td>';
            echo '<td>' . htmlspecialchars($invoice['F_inv_doc']) . '</td>';
            $creatorName = $invoice['F_realname'] ?: $invoice['F_username'];
            echo '<td>' . htmlspecialchars($creatorName) . '</td>';
            echo '<td>' . htmlspecialchars($invoice['F_CreatorTime']) . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    } else {
        // 导出CSV格式
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $filename . '.csv"');
        
        // 输出BOM以支持中文
        echo "\xEF\xBB\xBF";
        
        // 输出CSV头部
        $output = fopen('php://output', 'w');
        
        // 设置CSV列标题
        fputcsv($output, [
            '发票代码',
            '发票号码', 
            '开票日期',
            '发票金额',
            '使用人',
            '凭证号',
            '录入人',
            '录入时间'
        ]);
        
        // 输出数据行
        foreach ($invoices as $invoice) {
            $creatorName = $invoice['F_realname'] ?: $invoice['F_username'];
            fputcsv($output, [
                $invoice['F_inv_code'],
                $invoice['F_inv_num'],
                $invoice['F_inv_date'],
                '¥' . number_format($invoice['F_inv_money'], 2),
                $invoice['F_inv_user'],
                $invoice['F_inv_doc'],
                $creatorName,
                $invoice['F_CreatorTime']
            ]);
        }
        
        fclose($output);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => '导出失败: ' . $e->getMessage()
    ]);
}
?>