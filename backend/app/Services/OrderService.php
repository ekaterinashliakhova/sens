<?php

namespace App\Services;

class OrderService
{
    private string $ordersFile;

    public function __construct()
    {
        $this->ordersFile = __DIR__ . '/../../storage/orders.json';
        if (!file_exists($this->ordersFile)) {
            file_put_contents($this->ordersFile, json_encode([]));
        }
    }

    public function createOrder(array $data): array
    {
        $orders = $this->getOrders();
        
        $newId = count($orders) > 0 ? max(array_column($orders, 'id')) + 1 : 1;
        
        $order = [
            'id' => $newId,
            'name' => $data['name'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'event_type' => $data['event_type'] ?? '',
            'date' => $data['date'] ?? '',
            'time' => $data['time'] ?? '',
            'guests' => (int)($data['guests'] ?? 1),
            'created_at' => date('Y-m-d H:i:s'),
            'reminder_sent' => false
        ];

        $orders[] = $order;
        file_put_contents($this->ordersFile, json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $order;
    }

    public function getOrders(): array
    {
        if (!file_exists($this->ordersFile)) {
            return [];
        }
        
        $content = file_get_contents($this->ordersFile);
        return json_decode($content, true) ?: [];
    }

    public function getOrderById(int $id): ?array
    {
        $orders = $this->getOrders();
        foreach ($orders as $order) {
            if ($order['id'] === $id) {
                return $order;
            }
        }
        return null;
    }

    public function markReminderSent(int $orderId): bool
    {
        $orders = $this->getOrders();
        foreach ($orders as &$order) {
            if ($order['id'] === $orderId) {
                $order['reminder_sent'] = true;
                file_put_contents($this->ordersFile, json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                return true;
            }
        }
        return false;
    }

    public function getOrdersPendingReminder(): array
    {
        $orders = $this->getOrders();
        $pending = [];
        $now = time();

        foreach ($orders as $order) {
            if (!$order['reminder_sent']) {
                $createdAt = strtotime($order['created_at']);
                if ($now - $createdAt >= 86400) { // 24 hours
                    $pending[] = $order;
                }
            }
        }

        return $pending;
    }
}
