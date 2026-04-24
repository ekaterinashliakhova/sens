<?php

namespace App\Http\Controllers;

use App\Services\OrderService;
use App\Services\EmailService;

class ReminderController
{
    private OrderService $orderService;
    private EmailService $emailService;

    public function __construct()
    {
        $this->orderService = new OrderService();
        $this->emailService = new EmailService();
    }

    /**
     * Send reminders for orders created 24 hours ago
     */
    public function sendReminders(): array
    {
        $pendingOrders = $this->orderService->getOrdersPendingReminder();
        $sentCount = 0;
        $failedCount = 0;
        $results = [];

        foreach ($pendingOrders as $order) {
            $emailSent = $this->emailService->sendReminder($order);
            
            if ($emailSent) {
                $this->orderService->markReminderSent($order['id']);
                $sentCount++;
                $results[] = [
                    'order_id' => $order['id'],
                    'email' => $order['email'],
                    'status' => 'sent'
                ];
            } else {
                $failedCount++;
                $results[] = [
                    'order_id' => $order['id'],
                    'email' => $order['email'],
                    'status' => 'failed'
                ];
            }
        }

        return [
            'success' => true,
            'total_pending' => count($pendingOrders),
            'sent' => $sentCount,
            'failed' => $failedCount,
            'details' => $results
        ];
    }
}
