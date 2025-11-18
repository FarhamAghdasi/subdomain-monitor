<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\MonitorController;
use App\Services\Logger;

// تنظیم timezone
$config = require __DIR__ . '/../config/config.php';
date_default_timezone_set($config['app']['timezone']);

// ایجاد logger
$logger = new Logger($config['paths']['logs']);

// تشخیص حالت اجرا
$isCli = (php_sapi_name() === 'cli');
$isWebWithToken = (!$isCli && isset($_GET['token']));

if (!$isCli && !$isWebWithToken) {
    die('این اسکریپت فقط از طریق Command Line یا با توکن معتبر قابل اجرا است');
}

// اگر از طریق وب اجرا شده، توکن را بررسی کن
if ($isWebWithToken) {
    $token = $_GET['token'] ?? '';
    $validToken = $config['api']['token'];
    
    if (!$token || !hash_equals($validToken, $token)) {
        $logger->logError('Cron web access denied - Invalid token');
        header('HTTP/1.1 401 Unauthorized');
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Invalid API token',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    $logger->log('Cron job started via web with valid token');
}

if ($isCli) {
    echo "═══════════════════════════════════════════\n";
    echo "  سیستم مانیتورینگ ساب‌دامین\n";
    echo "  زمان: " . date('Y-m-d H:i:s') . "\n";
    echo "═══════════════════════════════════════════\n\n";
}

// اجرای مانیتور
$controller = new MonitorController($config);
$success = $controller->run();

if ($isCli) {
    echo "\n═══════════════════════════════════════════\n";
    exit($success ? 0 : 1);
} else {
    // پاسخ JSON برای وب
    header('Content-Type: application/json');
    if ($success) {
        $logger->log('Cron job completed successfully via web');
        echo json_encode([
            'success' => true,
            'message' => 'Monitoring completed successfully',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        $logger->logError('Cron job failed via web');
        echo json_encode([
            'success' => false,
            'error' => 'Monitoring failed', 
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}