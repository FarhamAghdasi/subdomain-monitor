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
        $this->logger->log('Starting subdomain monitoring process');
        
        try {
            // بررسی وجود فایل ساب دامین‌ها
            $subdomainsFile = $this->config['paths']['subdomains'];
            if (!file_exists($subdomainsFile)) {
                $error = "Subdomains file not found: $subdomainsFile";
                $this->logger->logError($error);
                $this->telegram->sendError($error);
                return false;
            }

            $this->logger->log("Reading subdomains from: $subdomainsFile");
            
            $subdomains = file($subdomainsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (empty($subdomains)) {
                $error = "No subdomains found in file";
                $this->logger->logError($error);
                return false;
            }

            $this->logger->log("Found " . count($subdomains) . " subdomains to check");

            $logModel = new LogModel($this->config['paths']['logs']);
            $checkedCount = 0;

            foreach ($subdomains as $subdomain) {
                $subdomain = trim($subdomain);
                if (empty($subdomain)) {
                    continue;
                }

                $this->logger->logDebug("Checking subdomain: $subdomain");
                
                try {
                    $result = $this->httpClient->check($subdomain);
                    $logModel->addResult($result);
                    $checkedCount++;

                    $this->logger->logDebug("Subdomain $subdomain: " . 
                        ($result->getStatus() ? 'ONLINE' : 'OFFLINE') . 
                        ", SSL: " . ($result->getHasSSL() ? 'YES' : 'NO'));

                } catch (\Exception $e) {
                    $this->logger->logError("Error checking $subdomain: " . $e->getMessage());
                }
            }

            $this->logger->log("Successfully checked $checkedCount subdomains");

            // ذخیره نتایج
            $saveResult = $logModel->save();
            if (!$saveResult) {
                $error = "Failed to save log file";
                $this->logger->logError($error);
                $this->telegram->sendError($error);
                return false;
            }

            $this->logger->log("Log file saved successfully");

            // ارسال نوتیفیکیشن اگر ساب دامین آفلاین وجود دارد
            $data = $logModel->getData();
            if ($data['offline'] > 0) {
                $this->telegram->send($logModel->getSummary());
            }

            $this->logger->log("Monitoring process completed successfully");
            return true;

        } catch (\Exception $e) {
            $error = "Critical error in monitoring process: " . $e->getMessage();
            $this->logger->logError($error);
            $this->telegram->sendError($error);
            return false;
        }
    }
}