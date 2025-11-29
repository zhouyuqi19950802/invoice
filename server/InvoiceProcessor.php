<?php
// InvoiceProcessor.php
require_once 'config.php';

class InvoiceProcessor {
    private $conn;
    private $table_name = "invoice_info";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // 检查发票是否重复
    public function checkDuplicate($qrCode) {
        try {
            $query = "SELECT F_inv_code, F_inv_num, F_inv_user, F_inv_doc 
                      FROM " . $this->table_name . " 
                      WHERE F_inv_qr = :qr_code 
                      LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":qr_code", $qrCode);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }

    // 解析二维码并提取发票信息
    public function parseQRCode($qrCode) {
        $parts = explode(',', $qrCode);
        
        if (count($parts) < 6) {
            return ['error' => '二维码格式不正确'];
        }

        // 解析关键字段
        $part2 = trim($parts[2] ?? '');
        $part3 = trim($parts[3] ?? '');
        $strPart = $part2 ? $part2 . '+' . $part3 : $part3;

        // 处理发票代码和号码
        list($F_inv_num, $F_inv_code) = strpos($strPart, '+') !== false 
            ? explode('+', $strPart) 
            : [$strPart, $strPart];

        // 处理发票日期
        $F_inv_date = trim($parts[5] ?? '');
        if (preg_match('/^\d{8}$/', $F_inv_date)) {
            $F_inv_date = substr($F_inv_date, 0, 4) . '-' . 
                         substr($F_inv_date, 4, 2) . '-' . 
                         substr($F_inv_date, 6, 2);
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $F_inv_date)) {
            $F_inv_date = '';
        }

        // 处理发票金额
        $F_inv_money = floatval(trim($parts[4] ?? 0));

        return [
            'F_inv_code' => $F_inv_code,
            'F_inv_num' => $F_inv_num,
            'F_inv_date' => $F_inv_date,
            'F_inv_money' => $F_inv_money,
            'F_inv_qr' => $qrCode
        ];
    }

    // 保存发票信息
    public function saveInvoice($data) {
        try {
            $query = "INSERT INTO " . $this->table_name . " 
                     (F_inv_code, F_inv_num, F_inv_date, F_inv_money, F_inv_qr, F_inv_user, F_inv_doc, F_creator_id, F_CreatorTime) 
                      VALUES 
                     (:code, :num, :date, :money, :qr, :user, :doc, :creator_id, NOW())";
            
            $stmt = $this->conn->prepare($query);
            
            return $stmt->execute([
                ':code' => $data['F_inv_code'],
                ':num' => $data['F_inv_num'],
                ':date' => $data['F_inv_date'],
                ':money' => $data['F_inv_money'],
                ':qr' => $data['F_inv_qr'],
                ':user' => $data['F_inv_user'] ?? '',
                ':doc' => $data['F_inv_doc'] ?? '',
                ':creator_id' => $data['F_creator_id']
            ]);
        } catch(PDOException $e) {
            return false;
        }
    }
}
?>