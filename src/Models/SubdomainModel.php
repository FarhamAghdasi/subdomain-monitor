<?php

namespace App\Models;

class SubdomainModel
{
    private $url;
    private $status;
    private $hasSSL;
    private $responseCode;
    private $responseTime;
    private $lastChecked;
    private $error;

    public function __construct($url)
    {
        $this->url = $url;
        $this->lastChecked = date('Y-m-d H:i:s');
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setStatus(bool $status): void
    {
        $this->status = $status;
    }

    public function getStatus(): bool
    {
        return $this->status ?? false;
    }

    public function setHasSSL(bool $hasSSL): void
    {
        $this->hasSSL = $hasSSL;
    }

    public function getHasSSL(): bool
    {
        return $this->hasSSL ?? false;
    }

    public function setResponseCode(?int $code): void
    {
        $this->responseCode = $code;
    }

    public function getResponseCode(): ?int
    {
        return $this->responseCode;
    }

    public function setResponseTime(float $time): void
    {
        $this->responseTime = round($time, 3);
    }

    public function getResponseTime(): ?float
    {
        return $this->responseTime;
    }

    public function setError(?string $error): void
    {
        $this->error = $error;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getLastChecked(): string
    {
        return $this->lastChecked;
    }

    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'status' => $this->status ? 'online' : 'offline',
            'has_ssl' => $this->hasSSL,
            'response_code' => $this->responseCode,
            'response_time' => $this->responseTime,
            'last_checked' => $this->lastChecked,
            'error' => $this->error,
        ];
    }
}
