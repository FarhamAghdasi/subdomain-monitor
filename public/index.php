<?php
require_once __DIR__ . '/../vendor/autoload.php';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سیستم مانیتورینگ ساب‌دامین</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .info-box {
            background: #f8f9fa;
            border-right: 4px solid #667eea;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .info-box h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .info-box p {
            color: #666;
            line-height: 1.8;
            margin-bottom: 8px;
        }

        .info-box code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            color: #d63384;
        }

        .endpoint {
            background: #667eea;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }

        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-top: 10px;
        }

        .status.active {
            background: #d4edda;
            color: #155724;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            color: #999;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🚀 سیستم مانیتورینگ ساب‌دامین</h1>
        <p class="subtitle">سیستم مانیتورینگ خودکار با معماری MVC</p>

        <div class="status active">✅ سیستم آماده به کار است</div>

        <div class="info-box">
            <h3>📡 API Endpoints</h3>
            <p>برای دسترسی به API از endpoints زیر استفاده کنید:</p>

            <div class="endpoint">
                GET /api.php?action=status&token=YOUR_TOKEN
            </div>
            <p>دریافت وضعیت آخرین بررسی</p>

            <div class="endpoint">
                GET /api.php?action=uptime&hours=24&token=YOUR_TOKEN
            </div>
            <p>دریافت آمار uptime (پیش‌فرض: 24 ساعت)</p>

            <div class="endpoint">
                GET /api.php?action=latest&token=YOUR_TOKEN
            </div>
            <p>دریافت کامل آخرین لاگ</p>

            <div class="endpoint">
                GET /api.php?action=health&token=YOUR_TOKEN
            </div>
            <p>بررسی سلامت سیستم</p>
        </div>

        <div class="info-box">
            <h3>⚙️ تنظیمات Cron Job</h3>
            <p>برای اجرای خودکار هر 1 ساعت:</p>
            <div class="endpoint" style="background: #28a745;">
                0 * * * * /usr/bin/php /path/to/cron.php
            </div>
        </div>

        <div class="info-box">
            <h3>🔐 امنیت</h3>
            <p>• احراز هویت با Bearer Token</p>
            <p>• Rate Limiting (60 req/min)</p>
            <p>• تایید Method (GET/POST)</p>
            <p>• هدرهای امنیتی (CSP, X-Frame-Options)</p>
        </div>

        <div class="footer">
            Powered by Subdomain Monitor v1.0
        </div>
    </div>
</body>

</html>