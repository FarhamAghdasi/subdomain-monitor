<?php

namespace App\Views;

class ApiResponse
{
    public function success(array $data, int $code = 200): void
    {
        $this->json([
            'success' => true,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s'),
        ], $code);
    }

    public function error(string $message, int $code = 400): void
    {
        $this->json([
            'success' => false,
            'error' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
        ], $code);
    }

    public function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}