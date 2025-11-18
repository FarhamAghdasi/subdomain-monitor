<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\ApiController;

// تنظیم timezone
$config = require __DIR__ . '/../config/config.php';
date_default_timezone_set($config['app']['timezone']);

// اجرای API Controller
$controller = new ApiController($config);
$controller->handle();
