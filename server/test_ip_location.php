<?php
// 测试IP地理位置查询功能
require_once 'IpLocation.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>IP地理位置查询测试</h1>";

try {
    $ipLocation = new IpLocation();
    
    // 测试IP地址
    $testIps = [
        '120.235.176.115',
        '127.0.0.1',
        '192.168.1.1',
        '8.8.8.8'
    ];
    
    echo "<h2>测试结果：</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>IP地址</th><th>地理位置</th><th>状态</th></tr>";
    
    foreach ($testIps as $ip) {
        $location = $ipLocation->getLocation($ip);
        $status = $location ? '成功' : '失败';
        $color = $location ? 'green' : 'red';
        echo "<tr>";
        echo "<td>{$ip}</td>";
        echo "<td>" . ($location ?: '未获取到') . "</td>";
        echo "<td style='color: {$color};'>{$status}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<h2>批量查询测试：</h2>";
    $locations = $ipLocation->getBatchLocations($testIps);
    echo "<pre>";
    print_r($locations);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>错误: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<h2>环境检查：</h2>";
echo "<ul>";
echo "<li>cURL扩展: " . (function_exists('curl_init') ? '✓ 已启用' : '✗ 未启用') . "</li>";
echo "<li>JSON扩展: " . (function_exists('json_encode') ? '✓ 已启用' : '✗ 未启用') . "</li>";
echo "<li>PDO扩展: " . (extension_loaded('pdo') ? '✓ 已启用' : '✗ 未启用') . "</li>";
echo "</ul>";

?>

