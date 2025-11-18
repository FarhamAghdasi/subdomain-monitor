<?php

namespace App\Models;

class LogModel
{
    private $logPath;
    private $data;

    public function __construct(string $logPath)
    {
        $this->logPath = $logPath;
        $this->data = [
            'timestamp' => date('Y-m-d H:i:s'),
            'total' => 0,
            'online' => 0,
            'offline' => 0,
            'with_ssl' => 0,
            'without_ssl' => 0,
            'results' => [],
        ];
    }

    public function addResult(SubdomainModel $subdomain): void
    {
        $this->data['total']++;
        
        if ($subdomain->getStatus()) {
            $this->data['online']++;
        } else {
            $this->data['offline']++;
        }

        if ($subdomain->getHasSSL()) {
            $this->data['with_ssl']++;
        } else {
            $this->data['without_ssl']++;
        }

        $this->data['results'][] = $subdomain->toArray();
    }

    public function save(): bool
    {
        $filename = $this->logPath . '/monitor_' . date('Y-m-d_H-i-s') . '.json';
        
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }

        return file_put_contents($filename, json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getSummary(): string
    {
        $summary = "📊 گزارش مانیتورینگ ساب‌دامین‌ها\n\n";
        $summary .= "⏰ زمان: {$this->data['timestamp']}\n";
        $summary .= "📝 تعداد کل: {$this->data['total']}\n";
        $summary .= "✅ آنلاین: {$this->data['online']}\n";
        $summary .= "❌ آفلاین: {$this->data['offline']}\n";
        $summary .= "🔒 دارای SSL: {$this->data['with_ssl']}\n";
        $summary .= "🔓 بدون SSL: {$this->data['without_ssl']}\n\n";

        if ($this->data['offline'] > 0) {
            $summary .= "⚠️ ساب‌دامین‌های آفلاین:\n";
            foreach ($this->data['results'] as $result) {
                if ($result['status'] === 'offline') {
                    $summary .= "• {$result['url']}";
                    if ($result['error']) {
                        $summary .= " ({$result['error']})";
                    }
                    $summary .= "\n";
                }
            }
        }

        return $summary;
    }

    public static function getLatestLog(string $logPath): ?array
    {
        if (!is_dir($logPath)) {
            return null;
        }

        $files = glob($logPath . '/monitor_*.json');
        if (empty($files)) {
            return null;
        }

        rsort($files);
        $content = file_get_contents($files[0]);
        
        return $content ? json_decode($content, true) : null;
    }

    public static function getUptime(string $logPath, int $hours = 24): array
    {
        if (!is_dir($logPath)) {
            return ['uptime' => 0, 'total_checks' => 0];
        }

        $files = glob($logPath . '/monitor_*.json');
        $cutoffTime = time() - ($hours * 3600);
        $totalChecks = 0;
        $successfulChecks = 0;
        $subdomainStats = [];

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                continue;
            }

            $data = json_decode(file_get_contents($file), true);
            if (!$data) {
                continue;
            }

            foreach ($data['results'] as $result) {
                $url = $result['url'];
                if (!isset($subdomainStats[$url])) {
                    $subdomainStats[$url] = ['total' => 0, 'online' => 0];
                }
                
                $subdomainStats[$url]['total']++;
                $totalChecks++;
                
                if ($result['status'] === 'online') {
                    $subdomainStats[$url]['online']++;
                    $successfulChecks++;
                }
            }
        }

        $uptime = $totalChecks > 0 ? round(($successfulChecks / $totalChecks) * 100, 2) : 0;

        return [
            'uptime' => $uptime,
            'total_checks' => $totalChecks,
            'successful_checks' => $successfulChecks,
            'failed_checks' => $totalChecks - $successfulChecks,
            'subdomain_stats' => $subdomainStats,
        ];
    }
}