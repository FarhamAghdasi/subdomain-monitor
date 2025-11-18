<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Controllers\MonitorController;

// تنظیم timezone
$config = require __DIR__ . '/config/config.php';
date_default_timezone_set($config['app']['timezone']);

// اطمینان از اجرا در CLI
if (php_sapi_name() !== 'cli') {
    die('این اسکریپت فقط از طریق Command Line قابل اجرا است');
}

echo "═══════════════════════════════════════════\n";
echo "  سیستم مانیتورینگ ساب‌دامین\n";
echo "  زمان: " . date('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════\n\n";

// اجرای مانیتور
$controller = new MonitorController($config);
$success = $controller->run();

echo "\n═══════════════════════════════════════════\n";

exit($success ? 0 : 1);