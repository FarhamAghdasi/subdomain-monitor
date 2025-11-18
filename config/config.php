<?php

return [
    'telegram' => [
        'bot_token' => 'YOUR_BOT_TOKEN_HERE', // توکن بات تلگرام
        'chat_id' => 'YOUR_CHAT_ID_HERE', // شناسه چت
    ],
    
    'api' => [
        'token' => 'YOUR_SECURE_API_TOKEN_HERE', // توکن API (مثال: bin2hex(random_bytes(32)))
        'allowed_methods' => ['GET', 'POST'],
        'rate_limit' => 60, // تعداد درخواست در دقیقه
    ],
    
    'monitor' => [
        'timeout' => 10, // تایم‌اوت درخواست (ثانیه)
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'verify_ssl' => true,
        'follow_redirects' => true,
        'max_redirects' => 5,
    ],
    
    'paths' => [
        'storage' => __DIR__ . '/../storage',
        'logs' => __DIR__ . '/../storage/logs',
        'subdomains' => __DIR__ . '/../subdomains.txt',
    ],
    
    'app' => [
        'timezone' => 'Asia/Tehran',
        'log_format' => 'Y-m-d H:i:s',
    ],
];