<?php

namespace App\Services;

class GigaChatService
{
    private string $credentials;
    private string $scope;
    private string $model;
    private ?string $accessToken = null;
    private ?int $tokenExpiresAt = null;

    public function __construct()
    {
        $this->credentials = $_ENV['GIGACHAT_CREDENTIALS'] ?? '';
        $this->scope = $_ENV['GIGACHAT_SCOPE'] ?? 'GIGACHAT_API_PERS';
        $this->model = $_ENV['GIGACHAT_MODEL'] ?? 'GigaChat-Max';
    }

    private function getAccessToken(): ?string
    {
        if ($this->accessToken && $this->tokenExpiresAt && time() < $this->tokenExpiresAt) {
            return $this->accessToken;
        }

        $url = 'https://ngw.devices.sberbank.ru:9443/api/v2/oauth';
        
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'RqUID: ' . bin2hex(random_bytes(16)),
            'Authorization: Basic ' . base64_encode($this->credentials)
        ];

        $data = http_build_query([
            'scope' => $this->scope
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return null;
        }

        $jsonData = json_decode($response, true);
        if (isset($jsonData['access_token'])) {
            $this->accessToken = $jsonData['access_token'];
            $this->tokenExpiresAt = time() + ($jsonData['expires_in'] ?? 1800) - 60;
            return $this->accessToken;
        }

        return null;
    }

    public function sendMessage(string $prompt): ?string
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return null;
        }

        $url = 'https://gigachat.devices.sberbank.ru/api/v2/chat/completions';
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ];

        $data = json_encode([
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Ты - виртуальный помощник музея Сенсориум по имени Лена. Отвечай вежливо, дружелюбно, без эмодзи.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
            'top_p' => 0.1,
            'n' => 1
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return null;
        }

        $jsonData = json_decode($response, true);
        if (isset($jsonData['choices'][0]['message']['content'])) {
            return $jsonData['choices'][0]['message']['content'];
        }

        return null;
    }
}
