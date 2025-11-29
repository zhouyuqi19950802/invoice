<?php
// 检查是否已安装
require_once 'server/InstallChecker.php';
if (!InstallChecker::isInstalled()) {
    InstallChecker::redirectToInstall();
}

require_once 'server/SecurityConfig.php';
require_once 'server/ConfigManager.php';

// 配置安全的Session
if (!SecurityConfig::configureSession()) {
    die("Session配置失败");
}

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// 加载系统配置
$configManager = new ConfigManager();
$siteFavicon = $configManager->getConfig('site_favicon', 'image/favicon.ico');
$loginLogo = $configManager->getConfig('login_logo', 'image/logo.png');
$loginTitle = $configManager->getConfig('login_title', '电子发票查重工具');
$loginDescription = $configManager->getConfig('login_description', '请登录您的账户');
$mainTitleText = $configManager->getConfig('main_title_text', '电子发票查重工具');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($mainTitleText); ?> - 登录</title>
    <link rel="icon" href="<?php echo htmlspecialchars($siteFavicon); ?>" type="image/x-icon">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/resources/fontawesome/all.min.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: url('image/bg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            padding: 20px;
        }
        
        .login-box {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header i {
            font-size: 48px;
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        .login-header h1 {
            font-size: 24px;
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: var(--gray);
            font-size: 14px;
        }
        
        .login-form .form-group {
            margin-bottom: 20px;
        }
        
        .login-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }
        
        .login-form .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .login-form .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(44, 123, 229, 0.1);
        }
        
        .login-btn {
            width: 100%;
            padding: 12px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .login-btn:hover {
            background: #1c6cd6;
        }
        
        .login-btn:disabled {
            background: var(--gray);
            cursor: not-allowed;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }
        
        .alert-error {
            background: #fdedf0;
            color: var(--danger);
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #e8f8f0;
            color: #00d97e;
            border: 1px solid #00d97e;
        }
        
        .form-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: var(--gray);
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 10px;
        }
        
        .spinner {
            border: 2px solid #f3f3f3;
            border-top: 2px solid var(--primary);
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* 移动端适配 */
        @media (max-width: 768px) {
            .login-container {
                padding: 15px;
                min-height: 100vh;
            }
            
            .login-box {
                padding: 30px 25px;
                border-radius: 12px;
            }
            
            .login-header {
                margin-bottom: 25px;
            }
            
            .login-header img {
                width: 60px !important;
                height: 60px !important;
                margin-bottom: 12px;
            }
            
            .login-header h1 {
                font-size: 20px;
                margin-bottom: 8px;
            }
            
            .login-header p {
                font-size: 13px;
            }
            
            .login-form .form-group {
                margin-bottom: 18px;
            }
            
            .login-form label {
                font-size: 14px;
                margin-bottom: 6px;
            }
            
            .login-form .form-control {
                padding: 11px 12px;
                font-size: 16px; /* 防止iOS自动缩放 */
            }
            
            .login-btn {
                padding: 13px;
                font-size: 15px;
                margin-top: 8px;
            }
            
            .alert {
                padding: 10px 12px;
                font-size: 13px;
                margin-bottom: 18px;
            }
            
            .form-footer {
                margin-top: 18px;
                font-size: 13px;
            }
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 10px;
            }
            
            .login-box {
                padding: 25px 20px;
            }
            
            .login-header img {
                width: 50px !important;
                height: 50px !important;
            }
            
            .login-header h1 {
                font-size: 18px;
            }
            
            .login-header p {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <img src="<?php echo htmlspecialchars($loginLogo); ?>" alt="<?php echo htmlspecialchars($loginTitle); ?>" style="width: 80px; height: 80px; margin-bottom: 15px;">
                <h1><?php echo htmlspecialchars($loginTitle); ?></h1>
                <p><?php echo htmlspecialchars($loginDescription); ?></p>
            </div>
            
            <div class="alert" id="errorAlert"></div>
            
            <form class="login-form" id="loginForm">
                <input type="hidden" id="csrf_token" value="<?php echo SecurityConfig::generateCSRFToken(); ?>">
                <div class="form-group">
                    <label for="username">用户名</label>
                    <input type="text" id="username" class="form-control" placeholder="请输入用户名" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">密码</label>
                    <input type="password" id="password" class="form-control" placeholder="请输入密码" required>
                </div>
                
                <button type="submit" class="login-btn" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i> 登录
                </button>
                
                <div class="loading" id="loadingLogin">
                    <div class="spinner"></div>
                    <p>登录中...</p>
                </div>
            </form>
            
            <div class="form-footer">
                <p></p>
                <p></p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            const loginBtn = document.getElementById('loginBtn');
            const errorAlert = document.getElementById('errorAlert');
            const loadingLogin = document.getElementById('loadingLogin');
            
            if (!username || !password) {
                showError('请输入用户名和密码');
                return;
            }
            
            // 显示加载中，隐藏按钮
            loginBtn.style.display = 'none';
            loadingLogin.style.display = 'block';
            errorAlert.style.display = 'none';
            
            try {
                const formData = new FormData();
                formData.append('username', username);
                formData.append('password', password);
                formData.append('csrf_token', document.getElementById('csrf_token').value);
                
                const response = await fetch('server/Auth.php?action=login', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // 登录成功，显示成功消息后跳转
                    showSuccess('登录成功，正在跳转...');
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1000);
                } else {
                    showError(result.message || '登录失败');
                    // 恢复登录按钮
                    loginBtn.style.display = 'block';
                    loadingLogin.style.display = 'none';
                }
            } catch (error) {
                console.error('登录错误:', error);
                showError('网络错误，请重试');
                // 恢复登录按钮
                loginBtn.style.display = 'block';
                loadingLogin.style.display = 'none';
            }
        });
        
        function showError(message) {
            const errorAlert = document.getElementById('errorAlert');
            errorAlert.textContent = message;
            errorAlert.style.display = 'block';
            errorAlert.className = 'alert alert-error';
            
            // 自动隐藏错误消息
            setTimeout(() => {
                errorAlert.style.display = 'none';
            }, 5000);
        }
        
        function showSuccess(message) {
            const errorAlert = document.getElementById('errorAlert');
            errorAlert.textContent = message;
            errorAlert.style.display = 'block';
            errorAlert.className = 'alert alert-success';
        }
        
        // 按回车键提交表单
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginForm').dispatchEvent(new Event('submit'));
            }
        });
        
        // 页面加载后自动聚焦到用户名输入框
        window.addEventListener('load', function() {
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>