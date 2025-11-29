<?php
// error_handler.php - 错误处理配置

// 设置错误报告级别
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 自定义错误处理函数
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    // 记录错误到日志
    $error_message = date('Y-m-d H:i:s') . " - Error: [$errno] $errstr in $errfile on line $errline\n";
    
    // 在开发环境中显示错误
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 10px; margin: 10px; border-radius: 4px;'>";
        echo "<strong>PHP Error:</strong> $errstr<br>";
        echo "File: $errfile<br>";
        echo "Line: $errline<br>";
        echo "Error Code: $errno<br>";
        echo "</div>";
    }
    
    // 记录到文件（如果可能）
    $logFile = __DIR__ . '/../logs/error.log';
    if (is_dir(dirname($logFile)) && is_writable(dirname($logFile))) {
        file_put_contents($logFile, $error_message, FILE_APPEND | LOCK_EX);
    }
    
    return true; // 防止PHP默认错误处理器
}

// 设置自定义错误处理器
set_error_handler('customErrorHandler');

// 异常处理函数
function customExceptionHandler($exception) {
    $error_message = date('Y-m-d H:i:s') . " - Uncaught exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine() . "\n";
    
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 10px; margin: 10px; border-radius: 4px;'>";
        echo "<strong>Fatal Error:</strong> " . $exception->getMessage() . "<br>";
        echo "File: " . $exception->getFile() . "<br>";
        echo "Line: " . $exception->getLine() . "<br>";
        echo "Trace: <pre>" . $exception->getTraceAsString() . "</pre>";
        echo "</div>";
    }
    
    $logFile = __DIR__ . '/../logs/error.log';
    if (is_dir(dirname($logFile)) && is_writable(dirname($logFile))) {
        file_put_contents($logFile, $error_message . $exception->getTraceAsString() . "\n\n", FILE_APPEND | LOCK_EX);
    }
    
    // 在生产环境中显示友好错误页面
    if (!defined('DEBUG_MODE') || !DEBUG_MODE) {
        http_response_code(500);
        include __DIR__ . '/../error_pages/500.html';
    }
}

// 设置异常处理器
set_exception_handler('customExceptionHandler');

// 致命错误处理
function shutdownHandler() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $error_message = date('Y-m-d H:i:s') . " - Fatal Error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line'] . "\n";
        
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 10px; margin: 10px; border-radius: 4px;'>";
            echo "<strong>Fatal Error:</strong> " . $error['message'] . "<br>";
            echo "File: " . $error['file'] . "<br>";
            echo "Line: " . $error['line'] . "<br>";
            echo "</div>";
        }
        
        $logFile = __DIR__ . '/../logs/error.log';
        if (is_dir(dirname($logFile)) && is_writable(dirname($logFile))) {
            file_put_contents($logFile, $error_message, FILE_APPEND | LOCK_EX);
        }
        
        // 在生产环境中显示友好错误页面
        if (!defined('DEBUG_MODE') || !DEBUG_MODE) {
            http_response_code(500);
            include __DIR__ . '/../error_pages/500.html';
        }
    }
}

register_shutdown_function('shutdownHandler');

// 创建日志目录（如果不存在）
if (!is_dir(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}

// 定义调试模式（开发环境为true，生产环境为false）
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', true); // 在生产环境中设置为 false
}
?>