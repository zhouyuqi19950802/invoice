<?php
/**
 * 电子发票查重工具 - 安装脚本
 * 用于初始化数据库和创建管理员账户
 */

// 安全检查：检查是否已安装，如果已安装则禁止访问
$lockFile = __DIR__ . '/.installed';
if (file_exists($lockFile) && !isset($_GET['force_install'])) {
    // 在生产环境中完全禁止访问
    // 为了调试，允许本地通过force_install参数访问
    $isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']);
    if (!$isLocal || !isset($_GET['force_install'])) {
        http_response_code(403);
        die('系统已完成安装，安装程序已被禁用。如需重新安装，请删除 install/.installed 文件。');
    }
}

// 检查是否已安装
function checkInstalled() {
    $lockFile = __DIR__ . '/.installed';
    if (file_exists($lockFile)) {
        return true;
    }
    
    // 检查是否已有配置文件
    $envFile = __DIR__ . '/.env';
    if (!file_exists($envFile)) {
        return false;
    }
    
    // 检查数据库是否已初始化
    try {
        require_once __DIR__ . '/../server/SecurityConfig.php';
        $config = SecurityConfig::getDbConfig();
        $host = $config['host'];
        $dbname = $config['name'];
        $username = $config['username'];
        $password = $config['password'];
        
        // 解析host（可能包含端口）
        $hostParts = explode(':', $host);
        $dbHost = $hostParts[0];
        $dbPort = isset($hostParts[1]) ? $hostParts[1] : '3306';
        
        $conn = new PDO(
            "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4",
            $username,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // 检查数据库是否存在且有表
        $stmt = $conn->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '{$dbname}'");
        $tableCount = $stmt->fetchColumn();
        
        if ($tableCount > 0) {
            return true;
        }
    } catch (Exception $e) {
        // 数据库未配置或连接失败，继续安装流程
    }
    
    return false;
}

// 处理安装请求
$installSuccess = false;
$errorMessage = '';
$step = isset($_GET['step']) ? $_GET['step'] : (isset($_POST['step']) ? $_POST['step'] : 'form');
$messages = [];
$adminUsername = '';
$adminRealname = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'install') {
    // 获取表单数据
    $dbHost = trim($_POST['db_host'] ?? '');
    $dbPort = trim($_POST['db_port'] ?? '3306');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUsername = trim($_POST['db_username'] ?? '');
    $dbPassword = trim($_POST['db_password'] ?? '');
    
    $adminUsername = trim($_POST['admin_username'] ?? '');
    $adminPassword = trim($_POST['admin_password'] ?? '');
    $adminRealname = trim($_POST['admin_realname'] ?? '');
    $adminConfirmPassword = trim($_POST['admin_confirm_password'] ?? '');
    
    // 验证输入
    if (empty($dbHost) || empty($dbName) || empty($dbUsername)) {
        $errorMessage = '请填写完整的数据库配置信息';
    } elseif (empty($adminUsername) || empty($adminPassword) || empty($adminRealname)) {
        $errorMessage = '请填写完整的管理员信息';
    } elseif ($adminPassword !== $adminConfirmPassword) {
        $errorMessage = '两次输入的密码不一致';
    } elseif (strlen($adminPassword) < 6) {
        $errorMessage = '管理员密码长度至少为6位';
    } else {
        // 开始安装
        try {
            // 步骤1: 连接MySQL（不指定数据库）
            $conn = new PDO(
                "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4",
                $dbUsername,
                $dbPassword,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $messages[] = '✓ 数据库服务器连接成功';
            
            // 步骤2: 创建数据库
            $conn->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $messages[] = '✓ 数据库创建成功';
            
            // 步骤3: 选择数据库并创建表
            $conn->exec("USE `{$dbName}`");
            
            // 创建用户表
            $conn->exec("
                CREATE TABLE IF NOT EXISTS users (
                    F_id INT PRIMARY KEY AUTO_INCREMENT,
                    F_username VARCHAR(50) NOT NULL UNIQUE COMMENT '用户名',
                    F_password VARCHAR(255) NOT NULL COMMENT '密码',
                    F_realname VARCHAR(50) COMMENT '真实姓名',
                    F_avatar VARCHAR(255) DEFAULT NULL COMMENT '用户头像路径',
                    F_role ENUM('admin', 'user') DEFAULT 'user' COMMENT '角色',
                    F_status TINYINT DEFAULT 1 COMMENT '状态：1-启用，0-禁用',
                    F_create_time DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
                    F_update_time DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
                    INDEX idx_username (F_username),
                    INDEX idx_status (F_status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表'
            ");
            
            // 创建发票信息表
            $conn->exec("
                CREATE TABLE IF NOT EXISTS invoice_info (
                    F_Id INT PRIMARY KEY AUTO_INCREMENT,
                    F_CreatorTime DATETIME DEFAULT CURRENT_TIMESTAMP,
                    F_inv_code VARCHAR(50) NOT NULL COMMENT '发票号码',
                    F_inv_num VARCHAR(50) NOT NULL COMMENT '发票代码',
                    F_inv_date VARCHAR(50) COMMENT '发票日期',
                    F_inv_money DECIMAL(50,3) DEFAULT 0.000 COMMENT '发票金额',
                    F_inv_user VARCHAR(50) COMMENT '发票使用人',
                    F_inv_doc VARCHAR(50) COMMENT '发票凭证号',
                    F_inv_qr VARCHAR(460) COMMENT '发票二维码',
                    F_inv_other VARCHAR(1000) COMMENT '其他备注',
                    F_creator_id INT COMMENT '录入人ID',
                    INDEX idx_inv_code (F_inv_code),
                    INDEX idx_inv_num (F_inv_num),
                    INDEX idx_creator_time (F_CreatorTime),
                    INDEX idx_inv_user (F_inv_user),
                    INDEX idx_creator_id (F_creator_id),
                    CONSTRAINT fk_invoice_creator FOREIGN KEY (F_creator_id) REFERENCES users(F_id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='发票信息表'
            ");
            
            // 创建系统日志表
            $conn->exec("
                CREATE TABLE IF NOT EXISTS system_logs (
                    F_id INT PRIMARY KEY AUTO_INCREMENT,
                    F_user_id INT COMMENT '用户ID',
                    F_username VARCHAR(50) COMMENT '用户名',
                    F_action VARCHAR(50) NOT NULL COMMENT '操作类型',
                    F_description TEXT COMMENT '操作描述',
                    F_ip_address VARCHAR(45) COMMENT 'IP地址',
                    F_user_agent TEXT COMMENT '用户代理',
                    F_target_type VARCHAR(50) COMMENT '目标类型',
                    F_target_id INT COMMENT '目标ID',
                    F_status TINYINT DEFAULT 1 COMMENT '状态：1-成功，0-失败',
                    F_error_message TEXT COMMENT '错误信息',
                    F_create_time DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
                    INDEX idx_user_id (F_user_id),
                    INDEX idx_action (F_action),
                    INDEX idx_create_time (F_create_time),
                    INDEX idx_ip_address (F_ip_address),
                    INDEX idx_status (F_status),
                    CONSTRAINT fk_log_user FOREIGN KEY (F_user_id) REFERENCES users(F_id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统日志表'
            ");
            
            // 创建系统配置表
            $conn->exec("
                CREATE TABLE IF NOT EXISTS system_config (
                    F_key VARCHAR(50) PRIMARY KEY COMMENT '配置键',
                    F_value TEXT COMMENT '配置值',
                    F_description VARCHAR(255) COMMENT '配置描述',
                    F_update_time DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置表'
            ");
            
            // 检查并添加头像字段（如果不存在）
            $stmt = $conn->query("
                SELECT COUNT(*) FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = '{$dbName}' 
                AND TABLE_NAME = 'users' 
                AND COLUMN_NAME = 'F_avatar'
            ");
            if ($stmt->fetchColumn() == 0) {
                $conn->exec("
                    ALTER TABLE users ADD COLUMN F_avatar VARCHAR(255) DEFAULT NULL COMMENT '用户头像路径' AFTER F_realname
                ");
            }
            
            // 插入默认配置
            $defaultConfigs = [
                ['site_favicon', 'image/favicon.ico', '网站标题图标路径'],
                ['login_logo', 'image/logo.png', '登录页面logo路径'],
                ['login_title', '电子发票查重工具', '登录页面logo下方第一行标题文字'],
                ['login_description', '请登录您的账户', '登录页面logo下方第二行描述文字'],
                ['main_logo', 'image/logo.png', '主页面左上角logo路径'],
                ['main_title_text', '电子发票查重工具', '主页面logo右侧文字']
            ];
            
            $configStmt = $conn->prepare("
                INSERT INTO system_config (F_key, F_value, F_description) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE F_value = VALUES(F_value), F_description = VALUES(F_description)
            ");
            
            foreach ($defaultConfigs as $config) {
                $configStmt->execute($config);
            }
            
            $messages[] = '✓ 数据表创建成功';
            
            // 步骤4: 创建管理员账户
            $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("
                INSERT INTO users (F_username, F_password, F_realname, F_role, F_status) 
                VALUES (:username, :password, :realname, 'admin', 1)
                ON DUPLICATE KEY UPDATE 
                    F_password = :password, 
                    F_realname = :realname,
                    F_role = 'admin',
                    F_status = 1
            ");
            $stmt->execute([
                ':username' => $adminUsername,
                ':password' => $hashedPassword,
                ':realname' => $adminRealname
            ]);
            $messages[] = '✓ 管理员账户创建成功';
            
            // 步骤5: 创建配置文件（保存到install文件夹）
            $envContent = "# 电子发票查重工具 - 数据库配置文件\n";
            $envContent .= "# 此文件由安装脚本自动生成，请妥善保管\n";
            $envContent .= "# 位置: install/.env\n\n";
            $envContent .= "DB_HOST={$dbHost}:{$dbPort}\n";
            $envContent .= "DB_NAME={$dbName}\n";
            $envContent .= "DB_USERNAME={$dbUsername}\n";
            $envContent .= "DB_PASSWORD=" . addslashes($dbPassword) . "\n";
            
            // 保存到install文件夹
            file_put_contents(__DIR__ . '/.env', $envContent);
            // 设置文件权限（只读）
            chmod(__DIR__ . '/.env', 0600);
            
            // 同时更新SecurityConfig.php的默认值（作为备用）
            $securityConfigFile = __DIR__ . '/../server/SecurityConfig.php';
            if (file_exists($securityConfigFile)) {
                $configContent = file_get_contents($securityConfigFile);
                // 更新默认配置值
                $configContent = preg_replace(
                    "/'host' => \$_ENV\['DB_HOST'\] \?\? '[^']*'/",
                    "'host' => \$_ENV['DB_HOST'] ?? '{$dbHost}:{$dbPort}'",
                    $configContent
                );
                $configContent = preg_replace(
                    "/'name' => \$_ENV\['DB_NAME'\] \?\? '[^']*'/",
                    "'name' => \$_ENV['DB_NAME'] ?? '{$dbName}'",
                    $configContent
                );
                $configContent = preg_replace(
                    "/'username' => \$_ENV\['DB_USERNAME'\] \?\? '[^']*'/",
                    "'username' => \$_ENV['DB_USERNAME'] ?? '{$dbUsername}'",
                    $configContent
                );
                $configContent = preg_replace(
                    "/'password' => \$_ENV\['DB_PASSWORD'\] \?\? '[^']*'/",
                    "'password' => \$_ENV['DB_PASSWORD'] ?? '" . addslashes($dbPassword) . "'",
                    $configContent
                );
                file_put_contents($securityConfigFile, $configContent);
            }
            
            // 加载环境变量
            if (file_exists(__DIR__ . '/.env')) {
                $envFile = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($envFile as $line) {
                    if (strpos(trim($line), '#') === 0) continue;
                    if (strpos($line, '=') !== false) {
                        list($key, $value) = explode('=', $line, 2);
                        $_ENV[trim($key)] = trim($value);
                    }
                }
            }
            
            // 步骤6: 创建安装锁定文件
            require_once __DIR__ . '/../server/InstallChecker.php';
            InstallChecker::createLockFile();
            $messages[] = '✓ 安装锁定文件创建成功';
            
            $messages[] = '✓ 配置文件创建成功';
            $installSuccess = true;
            $step = 'success';
            
        } catch (Exception $e) {
            $errorMessage = '安装失败: ' . $e->getMessage();
        }
    }
}

// 如果已安装，显示提示（但允许通过force_install强制访问）
if (checkInstalled() && $step !== 'install' && !isset($_GET['force']) && !isset($_GET['force_install'])) {
    $step = 'installed';
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>电子发票查重工具 - 系统安装</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', 'Microsoft YaHei', sans-serif;
            background: url('../image/bg.jpg') center center / cover no-repeat fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3);
            z-index: 0;
        }
        
        body > * {
            position: relative;
            z-index: 1;
        }
        
        .install-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.4);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        
        .install-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .install-header h1 {
            color: #2c7be5;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .install-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group label .required {
            color: #e63757;
            margin-left: 4px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e3ebf6;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #2c7be5;
            box-shadow: 0 0 0 3px rgba(44, 123, 229, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 120px;
            gap: 15px;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn-primary {
            background: #2c7be5;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1c6cd6;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(44, 123, 229, 0.3);
        }
        
        .btn-primary:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #fdedf0;
            color: #e63757;
            border: 1px solid #f8c5cc;
        }
        
        .alert-success {
            background: #e8f8f0;
            color: #00d97e;
            border: 1px solid #b8f5d3;
        }
        
        .alert-info {
            background: #e8f2ff;
            color: #2c7be5;
            border: 1px solid #b8d4f8;
        }
        
        .alert-warning {
            background: #fff4e5;
            color: #f6c343;
            border: 1px solid #f8e3a8;
        }
        
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e3ebf6;
        }
        
        .step {
            flex: 1;
            text-align: center;
            color: #ccc;
            font-size: 14px;
            position: relative;
        }
        
        .step.active {
            color: #2c7be5;
            font-weight: 500;
        }
        
        .step::after {
            content: '';
            position: absolute;
            bottom: -22px;
            left: 50%;
            transform: translateX(-50%);
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #e3ebf6;
        }
        
        .step.active::after {
            background: #2c7be5;
        }
        
        .messages {
            background: #f9fbfd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .messages p {
            margin: 8px 0;
            color: #333;
            font-size: 14px;
        }
        
        .help-text {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        
        @media (max-width: 600px) {
            .install-container {
                padding: 25px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <h1>🚀 系统安装</h1>
            <p>电子发票查重工具</p>
        </div>
        
        <?php if ($step === 'installed'): ?>
            <div class="alert alert-info">
                <strong>系统已安装</strong>
                <p style="margin-top: 10px;">系统已完成安装，如需重新安装，请删除 <code>.env</code> 文件后重新运行安装程序。</p>
                <p style="margin-top: 10px;">
                    <a href="../login.php" style="color: #2c7be5; text-decoration: none;">前往登录页面 →</a>
                </p>
            </div>
        <?php elseif ($step === 'success'): ?>
            <div class="alert alert-success">
                <strong>✓ 安装成功！</strong>
                <?php if (!empty($messages)): ?>
                    <div class="messages">
                        <?php foreach ($messages as $msg): ?>
                            <p><?php echo htmlspecialchars($msg); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <p style="margin-top: 15px;">
                    <strong>管理员账户信息：</strong><br>
                    用户名: <?php echo htmlspecialchars($adminUsername ?? ''); ?><br>
                    真实姓名: <?php echo htmlspecialchars($adminRealname ?? ''); ?>
                </p>
                <p style="margin-top: 20px;">
                    <a href="../login.php" class="btn btn-primary" style="display: inline-block; text-decoration: none; width: auto; padding: 12px 30px;">前往登录页面</a>
                </p>
            </div>
        <?php else: ?>
            <div class="step-indicator">
                <div class="step active">数据库配置</div>
                <div class="step active">管理员设置</div>
            </div>
            
            <?php if ($errorMessage): ?>
                <div class="alert alert-error">
                    <strong>错误：</strong> <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="?step=install">
                <input type="hidden" name="step" value="install">
                
                <h3 style="margin-bottom: 20px; color: #333; font-size: 18px;">数据库配置</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>数据库主机 <span class="required">*</span></label>
                        <input type="text" name="db_host" value="<?php echo htmlspecialchars($_POST['db_host'] ?? '127.0.0.1'); ?>" required>
                        <div class="help-text">MySQL服务器地址</div>
                    </div>
                    <div class="form-group">
                        <label>端口 <span class="required">*</span></label>
                        <input type="text" name="db_port" value="<?php echo htmlspecialchars($_POST['db_port'] ?? '3306'); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>数据库名称 <span class="required">*</span></label>
                    <input type="text" name="db_name" value="<?php echo htmlspecialchars($_POST['db_name'] ?? 'invoice'); ?>" required>
                    <div class="help-text">如果数据库不存在，将自动创建</div>
                </div>
                
                <div class="form-group">
                    <label>数据库用户名 <span class="required">*</span></label>
                    <input type="text" name="db_username" value="<?php echo htmlspecialchars($_POST['db_username'] ?? 'root'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>数据库密码</label>
                    <input type="password" name="db_password" value="<?php echo htmlspecialchars($_POST['db_password'] ?? ''); ?>">
                    <div class="help-text">如果没有密码，请留空</div>
                </div>
                
                <hr style="margin: 30px 0; border: none; border-top: 1px solid #e3ebf6;">
                
                <h3 style="margin-bottom: 20px; color: #333; font-size: 18px;">管理员账户设置</h3>
                
                <div class="form-group">
                    <label>管理员用户名 <span class="required">*</span></label>
                    <input type="text" name="admin_username" value="<?php echo htmlspecialchars($_POST['admin_username'] ?? 'admin'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>管理员真实姓名 <span class="required">*</span></label>
                    <input type="text" name="admin_realname" value="<?php echo htmlspecialchars($_POST['admin_realname'] ?? '系统管理员'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>管理员密码 <span class="required">*</span></label>
                    <input type="password" name="admin_password" required>
                    <div class="help-text">密码长度至少6位</div>
                </div>
                
                <div class="form-group">
                    <label>确认密码 <span class="required">*</span></label>
                    <input type="password" name="admin_confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">开始安装</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>

