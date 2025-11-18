<?php

namespace App\Controllers;

use App\Models\LogModel;
use App\Models\SubdomainModel;
use App\Services\HttpClient;
use App\Services\TelegramNotifier;

class MonitorController
{
    private $config;
    private $httpClient;
    private $telegram;
    private $logModel;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->httpClient = new HttpClient($config['monitor']);
        $this->telegram = new TelegramNotifier(
            $config['telegram']['bot_token'],
            $config['telegram']['chat_id']
        );
        $this->logModel = new LogModel($config['paths']['logs']);
    }

    public function run(): bool
    {
        try {
            echo "🚀 شروع مانیتورینگ...\n";
            
            // خواندن لیست ساب‌دامین‌ها
            $subdomains = $this->loadSubdomains();
            
            if (empty($subdomains)) {
                throw new \Exception('لیست ساب‌دامین‌ها خالی است');
            }

            echo "📝 تعداد ساب‌دامین‌ها: " . count($subdomains) . "\n\n";

            // بررسی هر ساب‌دامین
            $processed = 0;
            foreach ($subdomains as $subdomain) {
                $processed++;
                echo "[$processed/" . count($subdomains) . "] در حال بررسی: $subdomain ... ";
                
                $result = $this->httpClient->check($subdomain);
                $this->logModel->addResult($result);
                
                $status = $result->getStatus() ? '✅' : '❌';
                $ssl = $result->getHasSSL() ? '🔒' : '🔓';
                echo "$status $ssl";
                
                if ($result->getResponseTime()) {
                    echo " ({$result->getResponseTime()}s)";
                }
                
                echo "\n";
                
                // تاخیر کوچک برای جلوگیری از فشار بیش از حد
                usleep(100000); // 0.1 ثانیه
            }

            // ذخیره لاگ
            echo "\n💾 در حال ذخیره لاگ...\n";
            if (!$this->logModel->save()) {
                throw new \Exception('خطا در ذخیره لاگ');
            }

            // ارسال به تلگرام
            echo "📱 در حال ارسال گزارش به تلگرام...\n";
            $summary = $this->logModel->getSummary();
            
            if ($this->telegram->send($summary)) {
                echo "✅ گزارش با موفقیت ارسال شد\n";
            } else {
                echo "⚠️ خطا در ارسال گزارش به تلگرام\n";
            }

            echo "\n✅ مانیتورینگ با موفقیت انجام شد\n";
            return true;

        } catch (\Exception $e) {
            echo "❌ خطا: " . $e->getMessage() . "\n";
            $this->telegram->sendError($e->getMessage());
            return false;
        }
    }

    private function loadSubdomains(): array
    {
        $file = $this->config['paths']['subdomains'];
        
        if (!file_exists($file)) {
            throw new \Exception("فایل لیست ساب‌دامین‌ها یافت نشد: $file");
        }

        $content = file_get_contents($file);
        $lines = explode("\n", $content);
        
        $subdomains = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line) && !str_starts_with($line, '#')) {
                $subdomains[] = $line;
            }
        }

        return $subdomains;
    }
}
