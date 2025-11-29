<?php
require_once 'config.php';

class ConfigManager {
    private $conn;
    
    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }
    
    /**
     * 获取配置值
     * @param string $key 配置键
     * @param string $default 默认值
     * @return string 配置值
     */
    public function getConfig($key, $default = '') {
        try {
            $query = "SELECT F_value FROM system_config WHERE F_key = :key";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':key', $key);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                return $result['F_value'];
            }
            
            return $default;
        } catch (PDOException $e) {
            error_log("获取配置失败: " . $e->getMessage());
            return $default;
        }
    }
    
    /**
     * 设置配置值
     * @param string $key 配置键
     * @param string $value 配置值
     * @return array 结果数组
     */
    public function setConfig($key, $value) {
        try {
            // 检查配置是否存在
            $checkQuery = "SELECT F_key FROM system_config WHERE F_key = :key";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':key', $key);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                // 更新配置
                $updateQuery = "UPDATE system_config SET F_value = :value WHERE F_key = :key";
                $updateStmt = $this->conn->prepare($updateQuery);
                $updateStmt->bindParam(':value', $value);
                $updateStmt->bindParam(':key', $key);
                
                if ($updateStmt->execute()) {
                    return [
                        'success' => true,
                        'message' => '配置更新成功'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => '配置更新失败'
                    ];
                }
            } else {
                // 插入新配置
                $insertQuery = "INSERT INTO system_config (F_key, F_value) VALUES (:key, :value)";
                $insertStmt = $this->conn->prepare($insertQuery);
                $insertStmt->bindParam(':key', $key);
                $insertStmt->bindParam(':value', $value);
                
                if ($insertStmt->execute()) {
                    return [
                        'success' => true,
                        'message' => '配置保存成功'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => '配置保存失败'
                    ];
                }
            }
        } catch (PDOException $e) {
            error_log("设置配置失败: " . $e->getMessage());
            return [
                'success' => false,
                'message' => '设置配置失败: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取所有配置
     * @return array 所有配置数组
     */
    public function getAllConfigs() {
        try {
            $query = "SELECT F_key, F_value, F_description FROM system_config ORDER BY F_key";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $configs = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $configs[$row['F_key']] = [
                    'value' => $row['F_value'],
                    'description' => $row['F_description']
                ];
            }
            
            return [
                'success' => true,
                'data' => $configs
            ];
        } catch (PDOException $e) {
            error_log("获取所有配置失败: " . $e->getMessage());
            return [
                'success' => false,
                'message' => '获取配置失败: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 批量保存配置
     * @param array $configs 配置数组
     * @return array 结果数组
     */
    public function saveConfigs($configs) {
        // 检查是否有活动的事务
        $inTransaction = false;
        try {
            $inTransaction = $this->conn->inTransaction();
            
            if (!$inTransaction) {
                $this->conn->beginTransaction();
            }
            
            $updateQuery = "UPDATE system_config SET F_value = :value WHERE F_key = :key";
            $insertQuery = "INSERT INTO system_config (F_key, F_value) VALUES (:key, :value)";
            
            $updateStmt = $this->conn->prepare($updateQuery);
            $insertStmt = $this->conn->prepare($insertQuery);
            
            foreach ($configs as $key => $value) {
                // 检查配置是否存在
                $checkQuery = "SELECT F_key FROM system_config WHERE F_key = :key";
                $checkStmt = $this->conn->prepare($checkQuery);
                $checkStmt->bindParam(':key', $key);
                $checkStmt->execute();
                
                if ($checkStmt->rowCount() > 0) {
                    // 更新配置
                    $updateStmt->bindParam(':value', $value);
                    $updateStmt->bindParam(':key', $key);
                    if (!$updateStmt->execute()) {
                        if (!$inTransaction && $this->conn->inTransaction()) {
                            $this->conn->rollBack();
                        }
                        return [
                            'success' => false,
                            'message' => "更新配置 {$key} 失败"
                        ];
                    }
                } else {
                    // 插入新配置
                    $insertStmt->bindParam(':key', $key);
                    $insertStmt->bindParam(':value', $value);
                    if (!$insertStmt->execute()) {
                        if (!$inTransaction && $this->conn->inTransaction()) {
                            $this->conn->rollBack();
                        }
                        return [
                            'success' => false,
                            'message' => "保存配置 {$key} 失败"
                        ];
                    }
                }
            }
            
            if (!$inTransaction && $this->conn->inTransaction()) {
                $this->conn->commit();
            }
            
            return [
                'success' => true,
                'message' => '配置保存成功'
            ];
        } catch (PDOException $e) {
            if (!$inTransaction && $this->conn->inTransaction()) {
                try {
                    $this->conn->rollBack();
                } catch (PDOException $rollbackEx) {
                    // 忽略回滚错误
                }
            }
            error_log("批量保存配置失败: " . $e->getMessage());
            return [
                'success' => false,
                'message' => '保存配置失败: ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            if (!$inTransaction && $this->conn->inTransaction()) {
                try {
                    $this->conn->rollBack();
                } catch (Exception $rollbackEx) {
                    // 忽略回滚错误
                }
            }
            error_log("批量保存配置异常: " . $e->getMessage());
            return [
                'success' => false,
                'message' => '保存配置时发生异常: ' . $e->getMessage()
            ];
        }
    }
}

