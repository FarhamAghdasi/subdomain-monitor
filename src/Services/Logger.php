<?php

namespace App\Services;

class Logger
{
    private $logPath;
    private $logFile;

    public function __construct(string $logPath)
    {
        $this->logPath = $logPath;
        $this->logFile = $logPath . '/debug.log';
        
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    public function log(string $message, string $level = 'INFO'): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // همچنین در خروجی استاندارد نمایش داده شود
        if (php_sapi_name() === 'cli') {
            echo $logEntry;
        }
    }

    public function logError(string $message): void
    {
        $this->log($message, 'ERROR');
    }

    public function logWarning(string $message): void
    {
        $this->log($message, 'WARNING');
    }

    public function logDebug(string $message): void
    {
        $this->log($message, 'DEBUG');
    }
}