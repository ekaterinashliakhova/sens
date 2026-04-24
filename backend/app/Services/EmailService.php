<?php

namespace App\Services;

class EmailService
{
    private string $smtpHost;
    private int $smtpPort;
    private string $smtpUser;
    private string $smtpPass;
    private string $fromEmail;
    private string $fromName;

    public function __construct()
    {
        $this->smtpHost = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        $this->smtpPort = (int)($_ENV['SMTP_PORT'] ?? 587);
        $this->smtpUser = $_ENV['SMTP_USER'] ?? '';
        $this->smtpPass = $_ENV['SMTP_PASS'] ?? '';
        $this->fromEmail = $_ENV['FROM_EMAIL'] ?? 'noreply@sensorium.ru';
        $this->fromName = $_ENV['FROM_NAME'] ?? 'Сенсориум';
    }

    public function sendOrderConfirmation(array $order): bool
    {
        $to = $order['email'];
        $subject = 'Подтверждение заказа - Сенсориум';
        
        $body = $this->buildOrderEmailBody($order);
        
        return $this->sendEmail($to, $subject, $body);
    }

    public function sendReminder(array $order): bool
    {
        $to = $order['email'];
        $subject = 'Напоминание о посещении Сенсориума';
        
        $body = $this->buildReminderEmailBody($order);
        
        return $this->sendEmail($to, $subject, $body);
    }

    private function buildOrderEmailBody(array $order): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #F0C842;">Здравствуйте, {$order['name']}!</h2>
        
        <p>Благодарим вас за заказ в музее «Сенсориум».</p>
        
        <div style="background: #f9f9f9; padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h3>Детали вашего заказа:</h3>
            <p><strong>Номер заказа:</strong> #{$order['id']}</p>
            <p><strong>Тип мероприятия:</strong> {$order['event_type']}</p>
            <p><strong>Дата:</strong> {$order['date']}</p>
            <p><strong>Время:</strong> {$order['time']}</p>
            <p><strong>Количество гостей:</strong> {$order['guests']}</p>
        </div>
        
        <p>Мы ждём вас за 20 минут до начала программы для инструктажа.</p>
        
        <p><strong>Адрес музея:</strong> г. Москва, Старый Арбат, д. 17 (м. Арбатская)</p>
        
        <p>Если у вас возникнут вопросы, пожалуйста, свяжитесь с нами.</p>
        
        <p>С уважением,<br>Команда музея «Сенсориум»</p>
    </div>
</body>
</html>
HTML;
    }

    private function buildReminderEmailBody(array $order): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #F0C842;">Напоминание о посещении, {$order['name']}!</h2>
        
        <p>Напоминаем вам о предстоящем посещении музея «Сенсориум».</p>
        
        <div style="background: #f9f9f9; padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h3>Детали вашего визита:</h3>
            <p><strong>Тип мероприятия:</strong> {$order['event_type']}</p>
            <p><strong>Дата:</strong> {$order['date']}</p>
            <p><strong>Время:</strong> {$order['time']}</p>
            <p><strong>Количество гостей:</strong> {$order['guests']}</p>
        </div>
        
        <p>Пожалуйста, придите за 20 минут до начала программы.</p>
        
        <p><strong>Адрес музея:</strong> г. Москва, Старый Арбат, д. 17 (м. Арбатская)</p>
        
        <p>До встречи!</p>
        
        <p>С уважением,<br>Команда музея «Сенсориум»</p>
    </div>
</body>
</html>
HTML;
    }

    private function sendEmail(string $to, string $subject, string $body): bool
    {
        if (empty($this->smtpUser) || empty($this->smtpPass)) {
            error_log("SMTP credentials not configured. Email not sent to: $to");
            return false;
        }

        $headers = [
            'From' => "{$this->fromName} <{$this->fromEmail}>",
            'Reply-To' => $this->fromEmail,
            'X-Mailer' => 'PHP/' . phpversion(),
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/html; charset=UTF-8'
        ];

        $headerString = "";
        foreach ($headers as $key => $value) {
            $headerString .= "$key: $value\r\n";
        }

        return mail($to, $subject, $body, $headerString);
    }
}
