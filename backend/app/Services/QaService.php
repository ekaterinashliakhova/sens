<?php

namespace App\Services;

class QaService
{
    private string $qaFilePath;
    private array $qaPairs = [];

    public function __construct()
    {
        $this->qaFilePath = __DIR__ . '/../../storage/qa/QA';
        $this->loadQaPairs();
    }

    private function loadQaPairs(): void
    {
        if (!file_exists($this->qaFilePath)) {
            return;
        }

        $content = file_get_contents($this->qaFilePath);
        $lines = explode("\n", $content);
        
        $currentQuestion = null;
        $currentAnswer = [];

        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            
            if (empty($trimmedLine)) {
                if ($currentQuestion !== null && !empty($currentAnswer)) {
                    $this->qaPairs[] = [
                        'question' => $currentQuestion,
                        'answer' => implode("\n", $currentAnswer)
                    ];
                }
                $currentQuestion = null;
                $currentAnswer = [];
                continue;
            }

            if ($currentQuestion === null) {
                $currentQuestion = $trimmedLine;
            } else {
                $currentAnswer[] = $trimmedLine;
            }
        }

        // Save the last pair
        if ($currentQuestion !== null && !empty($currentAnswer)) {
            $this->qaPairs[] = [
                'question' => $currentQuestion,
                'answer' => implode("\n", $currentAnswer)
            ];
        }
    }

    public function findRelevantContext(string $userQuestion, int $topK = 3): array
    {
        $userQuestion = mb_strtolower($userQuestion);
        $scores = [];

        foreach ($this->qaPairs as $index => $pair) {
            $question = mb_strtolower($pair['question']);
            $answer = mb_strtolower($pair['answer']);
            
            // Simple similarity based on word overlap
            $userWords = preg_split('/\s+/', $userQuestion);
            $questionWords = preg_split('/\s+/', $question);
            $answerWords = preg_split('/\s+/', $answer);
            
            $overlap = 0;
            foreach ($userWords as $word) {
                if (mb_strlen($word) > 2) {
                    if (mb_strpos($question, $word) !== false) {
                        $overlap += 2;
                    }
                    if (mb_strpos($answer, $word) !== false) {
                        $overlap += 1;
                    }
                }
            }
            
            $scores[$index] = $overlap;
        }

        arsort($scores);
        $topIndices = array_slice(array_keys($scores), 0, $topK, true);

        $relevantContext = [];
        foreach ($topIndices as $index) {
            if ($scores[$index] > 0) {
                $relevantContext[] = $this->qaPairs[$index];
            }
        }

        return $relevantContext;
    }

    public function buildPrompt(string $userQuestion, array $context): string
    {
        $contextText = "";
        foreach ($context as $item) {
            $contextText .= "Вопрос: " . $item['question'] . "\n";
            $contextText .= "Ответ: " . $item['answer'] . "\n\n";
        }

        $prompt = <<<PROMPT
Ты - виртуальный помощник музея «Сенсориум» по имени Лена. Твоя задача - отвечать на вопросы пользователей вежливо, дружелюбно и информативно, основываясь на предоставленном контексте из базы знаний музея.

Контекст из базы знаний:
{$contextText}

Вопрос пользователя: {$userQuestion}

Если в контексте есть информация для ответа, используй её. Если информации недостаточно, ответь вежливо, что не можешь дать точный ответ, и предложи связаться с администратором музея.
Отвечай на русском языке, без эмодзи. Будь краткой, но информативной.
PROMPT;

        return $prompt;
    }

    public function getQaPairs(): array
    {
        return $this->qaPairs;
    }
}
