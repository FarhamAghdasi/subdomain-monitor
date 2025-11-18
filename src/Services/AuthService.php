<?php

namespace App\Services;

class AuthService
{
    private $validToken;
    private $allowedMethods;
    private $rateLimit;
    private $rateLimitFile;

    public function __construct(array $config)
    {
        $this->validToken = $config['token'];
        $this->allowedMethods = $config['allowed_methods'];
        $this->rateLimit = $config['rate_limit'];
        $this->rateLimitFile = sys_get_temp_dir() . '/api_rate_limit.json';
    }

    public function validateRequest(): array
    {
        // بررسی متد HTTP
        $method = $_SERVER['REQUEST_METHOD'];
        if (!in_array($method, $this->allowedMethods)) {
            return [
                'valid' => false,
                'error' => 'Method not allowed',
                'code' => 405,
            ];
        }

        // بررسی Content-Type برای POST
        if ($method === 'POST') {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (strpos($contentType, 'application/json') === false) {
                return [
                    'valid' => false,
                    'error' => 'Invalid Content-Type. Expected application/json',
                    'code' => 415,
                ];
            }
        }

        // بررسی توکن
        $token = $this->getToken();
        if (!$token || !hash_equals($this->validToken, $token)) {
            return [
                'valid' => false,
                'error' => 'Invalid or missing API token',
                'code' => 401,
            ];
        }

        // بررسی Rate Limit
        if (!$this->checkRateLimit()) {
            return [
                'valid' => false,
                'error' => 'Rate limit exceeded',
                'code' => 429,
            ];
        }

        return ['valid' => true];
    }

    private function getToken(): ?string
    {
        // بررسی در Header
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            if (preg_match('/Bearer\s+(.+)/', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }

        // بررسی در Query String
        return $_GET['token'] ?? null;
    }

    private function checkRateLimit(): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $now = time();
        $windowStart = $now - 60; // یک دقیقه گذشته

        // خواندن داده‌های موجود
        $data = [];
        if (file_exists($this->rateLimitFile)) {
            $content = file_get_contents($this->rateLimitFile);
            $data = json_decode($content, true) ?: [];
        }

        // پاک کردن درخواست‌های قدیمی
        if (isset($data[$ip])) {
            $data[$ip] = array_filter($data[$ip], function($timestamp) use ($windowStart) {
                return $timestamp > $windowStart;
            });
        } else {
            $data[$ip] = [];
        }

        // بررسی تعداد درخواست‌ها
        if (count($data[$ip]) >= $this->rateLimit) {
            return false;
        }

        // افزودن درخواست جدید
        $data[$ip][] = $now;

        // ذخیره داده‌ها
        file_put_contents($this->rateLimitFile, json_encode($data));

        return true;
    }

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}