<?php

namespace App\Controllers;

use App\Models\LogModel;
use App\Models\SubdomainModel;
use App\Services\HttpClient;
use App\Services\TelegramNotifier;
use App\Services\Logger;

class MonitorController
{
    private $config;
    private $httpClient;
    private $telegram;
    private $logger;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->httpClient = new HttpClient($config['monitor']);
        $this->telegram = new TelegramNotifier(
            $config['telegram']['bot_token'],
            $config['telegram']['chat_id']
        );
        $this->logger = new Logger($config['paths']['logs']);
    }

    public function run(): bool
    {
        $this->logger->log('شروع مانیتورینگ ساب‌دامین');
        
        try {
            // خواندن ساب‌دامین‌ها
            $subdomainsFile = $this->config['paths']['subdomains'];
            $subdomains = file($subdomainsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            if (empty($subdomains)) {
                throw new \Exception('فایل ساب‌دامین خالی است');
            }

            $logModel = new LogModel($this->config['paths']['logs']);

            // چک کردن هر ساب‌دامین
            foreach ($subdomains as $subdomain) {
                $subdomain = trim($subdomain);
                if (empty($subdomain)) continue;

                try {
                    $result = $this->httpClient->check($subdomain);
                    $logModel->addResult($result);
                } catch (\Exception $e) {
                    $this->logger->logError("خطا در چک کردن $subdomain: " . $e->getMessage());
                }
            }

            // ذخیره نتایج
            if (!$logModel->save()) {
                throw new \Exception('خطا در ذخیره لاگ');
            }

            // ارسال گزارش به تلگرام
            $monitoringData = $logModel->getData();
            $this->telegram->sendMonitoringReport($monitoringData);

            $this->logger->log('مانیتورینگ با موفقیت انجام شد');
            return true;

        } catch (\Exception $e) {
            $error = "خطا در مانیتورینگ: " . $e->getMessage();
            $this->logger->logError($error);
            $this->telegram->sendError($error);
            return false;
        }
    }
}