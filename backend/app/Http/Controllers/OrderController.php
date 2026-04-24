<?php

namespace App\Http\Controllers;

use App\Services\OrderService;
use App\Services\EmailService;

class OrderController
{
    private OrderService $orderService;
    private EmailService $emailService;

    public function __construct()
    {
        $this->orderService = new OrderService();
        $this->emailService = new EmailService();
    }

    public function createOrder(array $request): array
    {
        $requiredFields = ['name', 'email', 'phone', 'event_type', 'date', 'time', 'guests'];
        
        foreach ($requiredFields as $field) {
            if (empty($request[$field])) {
                return ['success' => false, 'error' => "Field '{$field}' is required"];
            }
        }

        // Validate email
        if (!filter_var($request['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email format'];
        }

        // Create order
        $order = $this->orderService->createOrder($request);

        // Send confirmation email
        $emailSent = $this->emailService->sendOrderConfirmation($order);

        return [
            'success' => true,
            'order' => $order,
            'email_sent' => $emailSent,
            'message' => 'Заказ успешно создан. Подтверждение отправлено на вашу почту.'
        ];
    }

    public function getOrders(): array
    {
        $orders = $this->orderService->getOrders();
        return ['success' => true, 'orders' => $orders];
    }

    public function getOrder(int $id): array
    {
        $order = $this->orderService->getOrderById($id);
        
        if (!$order) {
            return ['success' => false, 'error' => 'Order not found'];
        }

        return ['success' => true, 'order' => $order];
    }
}
