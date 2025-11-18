<?php

namespace App\Controllers;

use App\Models\LogModel;
use App\Services\AuthService;
use App\Views\ApiResponse;

class ApiController
{
    private $config;
    private $auth;
    private $response;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->auth = new AuthService($config['api']);
        $this->response = new ApiResponse();
    }

    public function handle(): void
    {
        // تنظیم هدرهای CORS و امنیتی
        $this->setSecurityHeaders();

        // اعتبارسنجی درخواست
        $validation = $this->auth->validateRequest();
        
        if (!$validation['valid']) {
            $this->response->error(
                $validation['error'],
                $validation['code']
            );
            return;
        }

        // مسیریابی
        $action = $_GET['action'] ?? 'status';

        try {
            switch ($action) {
                case 'status':
                    $this->getStatus();
                    break;
                
                case 'uptime':
                    $this->getUptime();
                    break;
                
                case 'latest':
                    $this->getLatestLog();
                    break;
                
                case 'health':
                    $this->getHealth();
                    break;
                
                default:
                    $this->response->error('Invalid action', 400);
            }
        } catch (\Exception $e) {
            $this->response->error(
                'Internal server error: ' . $e->getMessage(),
                500
            );
        }
    }

    private function getStatus(): void
    {
        $log = LogModel::getLatestLog($this->config['paths']['logs']);
        
        if (!$log) {
            $this->response->error('No monitoring data available', 404);
            return;
        }

        $this->response->success([
            'timestamp' => $log['timestamp'],
            'summary' => [
                'total' => $log['total'],
                'online' => $log['online'],
                'offline' => $log['offline'],
                'with_ssl' => $log['with_ssl'],
                'without_ssl' => $log['without_ssl'],
            ],
            'uptime_percentage' => round(($log['online'] / $log['total']) * 100, 2),
        ]);
    }

    private function getUptime(): void
    {
        $hours = (int)($_GET['hours'] ?? 24);
        
        if ($hours < 1 || $hours > 168) { // حداکثر یک هفته
            $this->response->error('Invalid hours parameter (1-168)', 400);
            return;
        }

        $uptimeData = LogModel::getUptime($this->config['paths']['logs'], $hours);
        
        $this->response->success([
            'period_hours' => $hours,
            'overall_uptime' => $uptimeData['uptime'],
            'total_checks' => $uptimeData['total_checks'],
            'successful_checks' => $uptimeData['successful_checks'],
            'failed_checks' => $uptimeData['failed_checks'],
        ]);
    }

    private function getLatestLog(): void
    {
        $log = LogModel::getLatestLog($this->config['paths']['logs']);
        
        if (!$log) {
            $this->response->error('No monitoring data available', 404);
            return;
        }

        $this->response->success($log);
    }

    private function getHealth(): void
    {
        $logsPath = $this->config['paths']['logs'];
        $subdomainsFile = $this->config['paths']['subdomains'];
        
        $health = [
            'status' => 'healthy',
            'checks' => [
                'logs_directory' => is_dir($logsPath) && is_writable($logsPath),
                'subdomains_file' => file_exists($subdomainsFile) && is_readable($subdomainsFile),
                'latest_log_exists' => LogModel::getLatestLog($logsPath) !== null,
            ],
        ];

        // اگر هر یک از چک‌ها ناموفق بود
        foreach ($health['checks'] as $check => $status) {
            if (!$status) {
                $health['status'] = 'unhealthy';
                break;
            }
        }

        $code = $health['status'] === 'healthy' ? 200 : 503;
        $this->response->json($health, $code);
    }

    private function setSecurityHeaders(): void
    {
        // جلوگیری از Clickjacking
        header('X-Frame-Options: DENY');
        
        // جلوگیری از XSS
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        
        // CORS
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST');
        header('Access-Control-Allow-Headers: Authorization, Content-Type');
        
        // Content Security Policy
        header("Content-Security-Policy: default-src 'none'");
        
        // تنظیم Content-Type
        header('Content-Type: application/json; charset=utf-8');

        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}