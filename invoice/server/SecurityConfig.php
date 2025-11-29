<?php
// SecurityConfig.php - 安全配置类
class SecurityConfig {
    private static $envLoaded = false;
    
    // 加载.env文件
    private static function loadEnvFile() {
        if (self::$envLoaded) {
            return;
        }
        
        // 优先从install文件夹读取.env文件
        $envFile = __DIR__ . '/../install/.env';
        // 如果install文件夹中没有，尝试从根目录读取（兼容旧配置）
        if (!file_exists($envFile)) {
            $envFile = __DIR__ . '/../.env';
        }
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                // 跳过注释
                if (strpos($line, '#') === 0) {
                    continue;
                }
                // 解析 KEY=VALUE 格式
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    // 移除引号
                    if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                        (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                        $value = substr($value, 1, -1);
                    }
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                    
                    if (function_exists('putenv')) {
                        putenv("$key=$value");
                    }
                }
            }
        }
        
        self::$envLoaded = true;
    }
    
    // 数据库配置（应该从环境变量获取）
    public static function getDbConfig() {
        // 加载.env文件
        self::loadEnvFile();
        
        return [
            'host' => $_ENV['DB_HOST'] ?? '127.0.0.1:3306',
            'name' => $_ENV['DB_NAME'] ?? 'invoice',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? 'Zyq-2710467'
        ];
    }
    
    // Session安全配置
    public static function configureSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // 检测是否通过反向代理（内网穿透）
            $isProxied = isset($_SERVER['HTTP_X_FORWARDED_FOR']) || 
                         isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ||
                         isset($_SERVER['HTTP_X_REAL_IP']);
            
            // 检测是否使用HTTPS（考虑反向代理的情况）
            $isHttps = false;
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                $isHttps = true;
            } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
                $isHttps = true;
            } elseif (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
                $isHttps = true;
            } elseif (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
                $isHttps = true;
            }
            
            // 防止Session Fixation
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_httponly', 1);
            // 在反向代理环境下，如果检测到HTTPS，则使用secure cookie
            ini_set('session.cookie_secure', $isHttps ? 1 : 0);
            // 反向代理环境下，使用Lax而不是Strict，以确保cookie能正常传递
            ini_set('session.cookie_samesite', $isProxied ? 'Lax' : 'Strict');
            ini_set('session.gc_maxlifetime', 3600); // 1小时过期
            
            // 强制使用安全的会话ID
            ini_set('session.sid_length', 48);
            ini_set('session.use_strict_mode', 1);
            
            session_start();
            
            // Session劫持保护 - 检查User-Agent
            // 在反向代理环境下，User-Agent可能会被修改，所以放宽检查
            $currentUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            if (!isset($_SESSION['user_agent'])) {
                $_SESSION['user_agent'] = $currentUserAgent;
            } elseif ($isProxied) {
                // 反向代理环境下，只检查User-Agent是否存在，不强制完全匹配
                // 这样可以避免因为代理添加额外头部导致session被销毁
                if (empty($currentUserAgent) && !empty($_SESSION['user_agent'])) {
                    // 如果User-Agent突然消失，可能是异常情况
                    session_destroy();
                    return false;
                }
                // 更新User-Agent（代理可能会修改它）
                $_SESSION['user_agent'] = $currentUserAgent;
            } elseif ($_SESSION['user_agent'] !== $currentUserAgent) {
                // 非代理环境下，严格检查User-Agent
                session_destroy();
                return false;
            }
            
            // Session过期检查
            if (!isset($_SESSION['last_activity'])) {
                $_SESSION['last_activity'] = time();
            } elseif (time() - $_SESSION['last_activity'] > 3600) {
                // 超过1小时无活动
                session_destroy();
                return false;
            } else {
                $_SESSION['last_activity'] = time();
            }
        }
        return true;
    }
    
    // CSRF Token生成和验证
    public static function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        
        // Token有效期1小时
        if (time() - $_SESSION['csrf_token_time'] > 3600) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        
        return $_SESSION['csrf_token'];
    }
    
    public static function validateCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token']) || !isset($token)) {
            return false;
        }
        
        // 检查token是否匹配
        if (!hash_equals($_SESSION['csrf_token'], $token)) {
            return false;
        }
        
        // 检查token是否过期
        if (time() - $_SESSION['csrf_token_time'] > 3600) {
            return false;
        }
        
        return true;
    }
    
    // 输入验证和过滤
    public static function sanitizeInput($input, $type = 'string') {
        if ($input === null) {
            return null;
        }
        
        switch ($type) {
            case 'int':
                return filter_var($input, FILTER_VALIDATE_INT);
            case 'email':
                return filter_var($input, FILTER_VALIDATE_EMAIL);
            case 'url':
                return filter_var($input, FILTER_VALIDATE_URL);
            case 'float':
                return filter_var($input, FILTER_VALIDATE_FLOAT);
            case 'string':
            default:
                return htmlspecialchars(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }
    
    // XSS防护 - 更全面的HTML过滤
    public static function escapeXSS($string) {
        if ($string === null) {
            return null;
        }
        
        // 基本的HTML转义
        $string = htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // 额外的XSS模式清理
        $xss_patterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*?<\/script>/mi' => '',
            '/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*?<\/iframe>/mi' => '',
            '/<object\b[^<]*(?:(?!<\/object>)<[^<]*)*?<\/object>/mi' => '',
            '/<embed\b[^<]*(?:(?!<\/embed>)<[^<]*)*?<\/embed>/mi' => '',
            '/<applet\b[^<]*(?:(?!<\/applet>)<[^<]*)*?<\/applet>/mi' => '',
            '/<meta\b[^<]*(?:(?!<\/meta>)<[^<]*)*?<\/meta>/mi' => '',
            '/<link\b[^<]*(?:(?!<\/link>)<[^<]*)*?<\/link>/mi' => '',
            '/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*?<\/style>/mi' => '',
            '/javascript:/i' => '',
            '/vbscript:/i' => '',
            '/onload\s*=/i' => '',
            '/onerror\s*=/i' => '',
            '/onclick\s*=/i' => '',
            '/onmouseover\s*=/i' => '',
        ];
        
        foreach ($xss_patterns as $pattern => $replacement) {
            $string = preg_replace($pattern, $replacement, $string);
        }
        
        return $string;
    }
    
    // SQL注入防护 - 验证表名和列名
    public static function validateIdentifier($identifier) {
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $identifier);
    }
    
    // 登录速率限制
    public static function checkLoginRate($username, $maxAttempts = 5, $lockoutTime = 900) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $key = 'login_attempts_' . md5($username);
        $lockoutKey = 'login_locked_' . md5($username);
        
        // 检查是否被锁定
        if (isset($_SESSION[$lockoutKey])) {
            if (time() - $_SESSION[$lockoutKey] < $lockoutTime) {
                $remainingTime = $lockoutTime - (time() - $_SESSION[$lockoutKey]);
                return [
                    'allowed' => false,
                    'message' => "账户已被锁定，请在 {$remainingTime} 秒后重试"
                ];
            } else {
                // 锁定时间已过，清除记录
                unset($_SESSION[$lockoutKey]);
                unset($_SESSION[$key]);
            }
        }
        
        // 检查尝试次数
        if (isset($_SESSION[$key])) {
            $attempts = $_SESSION[$key]['attempts'];
            $firstAttempt = $_SESSION[$key]['first_attempt'];
            
            if ($attempts >= $maxAttempts) {
                // 锁定账户
                $_SESSION[$lockoutKey] = time();
                return [
                    'allowed' => false,
                    'message' => "登录失败次数过多，账户已锁定 {$lockoutTime} 秒"
                ];
            }
            
            return ['allowed' => true];
        }
        
        return ['allowed' => true];
    }
    
    // 记录登录尝试
    public static function recordLoginAttempt($username, $success) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $key = 'login_attempts_' . md5($username);
        
        if ($success) {
            // 登录成功，清除记录
            unset($_SESSION[$key]);
        } else {
            // 登录失败，记录尝试
            if (!isset($_SESSION[$key])) {
                $_SESSION[$key] = [
                    'attempts' => 1,
                    'first_attempt' => time()
                ];
            } else {
                $_SESSION[$key]['attempts']++;
            }
        }
    }
    
    // 安全错误处理
    public static function handleSecurityError($message, $logFile = 'security.log') {
        // 记录到日志
        $logEntry = date('Y-m-d H:i:s') . ' - ' . $message . ' - IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . PHP_EOL;
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // 生产环境不显示详细错误
        if ($_ENV['APP_ENV'] === 'production') {
            error_log('Security violation detected');
            return '发生错误，请联系管理员';
        } else {
            return $message;
        }
    }
    
    // 检查文件上传安全
    public static function validateFileUpload($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'ico']) {
        // 检查文件是否存在
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['valid' => false, 'message' => '无效的文件上传'];
        }
        
        // 检查文件大小（5MB限制）
        if ($file['size'] > 5 * 1024 * 1024) {
            return ['valid' => false, 'message' => '文件大小超过限制'];
        }
        
        // 获取文件扩展名
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedTypes)) {
            return ['valid' => false, 'message' => '不支持的文件类型'];
        }
        
        // 检查MIME类型
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimeTypes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/x-icon'
        ];
        
        if (!in_array($mimeType, $allowedMimeTypes)) {
            return ['valid' => false, 'message' => '文件类型验证失败'];
        }
        
        // 生成安全的文件名
        $newFileName = uniqid('file_', true) . '.' . $extension;
        
        return [
            'valid' => true,
            'new_name' => $newFileName,
            'extension' => $extension
        ];
    }
}
?>