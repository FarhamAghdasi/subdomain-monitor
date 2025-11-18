<?php

namespace App\Services;

use App\Models\LogModel;

class TelegramNotifier
{
    private $botToken;
    private $chatId;

    public function __construct(string $botToken, string $chatId)
    {
        $this->botToken = $botToken;
        $this->chatId = $chatId;
    }

    public function send(string $message): bool
    {
        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";

        $data = [
            'chat_id' => $this->chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }

    public function sendError(string $error): bool
    {
        return $this->send("❌ <b>خطا در مانیتورینگ</b>\n" . htmlspecialchars($error));
    }

    /**
     * ارسال گزارش کامل مانیتورینگ
     */
    public function sendMonitoringReport(array $logData): bool
    {
        $message = $this->createSummaryMessage($logData);
        return $this->send($message);
    }

    /**
     * ایجاد پیام خلاصه
     */
    private function createSummaryMessage(array $data): string
    {
        $total = $data['total'];
        $online = $data['online'];
        $offline = $data['offline'];
        $withSSL = $data['with_ssl'];
        $uptimePercentage = $total > 0 ? round(($online / $total) * 100, 2) : 0;

        $message = "📊 <b>گزارش مانیتورینگ</b>\n";
        $message .= "⏰ " . date('H:i') . "\n\n";
        
        $message .= "✅ آنلاین: <b>$online</b>\n";
        $message .= "❌ آفلاین: <b>$offline</b>\n"; 
        $message .= "🔐 دارای SSL: <b>$withSSL</b>\n";
        $message .= "📡 آپتایم: <b>$uptimePercentage%</b>\n";

        // اگر آفلاین وجود دارد، لیست کن
        if ($offline > 0) {
            $message .= "\n🔻 آفلاین‌ها:\n";
            $offlineCount = 0;
            $offlineDomains = [];
            
            // جمع‌آوری دامنه‌های آفلاین
            foreach ($data['results'] as $result) {
                if ($result['status'] === 'offline') {
                    $offlineCount++;
                    // استفاده مستقیم از URL بدون تجزیه
                    $domain = $result['url'];
                    $offlineDomains[] = $domain;
                    
                    // فقط ۵ تا اول رو نشون بده
                    if ($offlineCount >= 5) break;
                }
            }
            
            // نمایش دامنه‌ها
            foreach ($offlineDomains as $domain) {
                $message .= "• $domain\n";
            }
            
            // اگر بیشتر از ۵ تا هست
            if ($offline > 5) {
                $remaining = $offline - 5;
                $message .= "• و $remaining مورد دیگر...\n";
            }
        }

        return $message;
    }
}