<?php

namespace App\Services;

use App\Models\SubdomainModel;

class HttpClient
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function check(string $url): SubdomainModel
    {
        $subdomain = new SubdomainModel($url);
        
        // ابتدا HTTPS را امتحان می‌کنیم
        $httpsUrl = 'https://' . $url;
        $result = $this->makeRequest($httpsUrl);
        
        if ($result['success']) {
            $subdomain->setStatus(true);
            $subdomain->setHasSSL(true);
            $subdomain->setResponseCode($result['code']);
            $subdomain->setResponseTime($result['time']);
        } else {
            // اگر HTTPS کار نکرد، HTTP را امتحان می‌کنیم
            $httpUrl = 'http://' . $url;
            $httpResult = $this->makeRequest($httpUrl);
            
            if ($httpResult['success']) {
                $subdomain->setStatus(true);
                $subdomain->setHasSSL(false);
                $subdomain->setResponseCode($httpResult['code']);
                $subdomain->setResponseTime($httpResult['time']);
            } else {
                $subdomain->setStatus(false);
                $subdomain->setHasSSL(false);
                $subdomain->setError($httpResult['error']);
            }
        }

        return $subdomain;
    }

    private function makeRequest(string $url): array
    {
        $startTime = microtime(true);
        
        $ch = curl_init($url);
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => $this->config['follow_redirects'],
            CURLOPT_MAXREDIRS => $this->config['max_redirects'],
            CURLOPT_TIMEOUT => $this->config['timeout'],
            CURLOPT_CONNECTTIMEOUT => $this->config['timeout'],
            CURLOPT_SSL_VERIFYPEER => $this->config['verify_ssl'],
            CURLOPT_SSL_VERIFYHOST => $this->config['verify_ssl'] ? 2 : 0,
            CURLOPT_USERAGENT => $this->config['user_agent'],
            CURLOPT_NOBODY => true, // فقط HEAD request
            CURLOPT_HEADER => false,
        ]);

        curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        $endTime = microtime(true);
        $responseTime = $endTime - $startTime;

        // کدهای 200-399 را موفق در نظر می‌گیریم
        $success = ($responseCode >= 200 && $responseCode < 400);

        return [
            'success' => $success && empty($error),
            'code' => $responseCode ?: null,
            'time' => $responseTime,
            'error' => $error ?: ($success ? null : "HTTP $responseCode"),
        ];
    }
}
