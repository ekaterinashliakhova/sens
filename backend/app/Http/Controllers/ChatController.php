<?php

namespace App\Http\Controllers;

use App\Services\QaService;
use App\Services\GigaChatService;
use App\Services\OrderService;
use App\Services\EmailService;

class ChatController
{
    private QaService $qaService;
    private GigaChatService $gigaChatService;

    public function __construct()
    {
        $this->qaService = new QaService();
        $this->gigaChatService = new GigaChatService();
    }

    public function sendMessage(array $request): array
    {
        $userMessage = $request['message'] ?? '';
        
        if (empty($userMessage)) {
            return ['success' => false, 'error' => 'Message is required'];
        }

        // RAG: Find relevant context from QA file
        $context = $this->qaService->findRelevantContext($userMessage);
        
        // Build prompt with context
        $prompt = $this->qaService->buildPrompt($userMessage, $context);
        
        // Send to GigaChat
        $response = $this->gigaChatService->sendMessage($prompt);
        
        if ($response === null) {
            // Fallback response if GigaChat fails
            $response = "Извините, я не смогла обработать ваш запрос. Пожалуйста, попробуйте ещё раз или свяжитесь с администратором музея.";
        }

        return [
            'success' => true,
            'response' => $response,
            'context_used' => count($context) > 0
        ];
    }
}
