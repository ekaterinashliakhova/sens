<?php

namespace App\Services;

use PDO;
use PDOException;

class OrderService
{
    private PDO $db;

    public function __construct()
    {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $dbname = $_ENV['DB_NAME'] ?? 'sensorium';
        $username = $_ENV['DB_USER'] ?? 'root';
        $password = $_ENV['DB_PASS'] ?? '';
        
        try {
            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
            $this->db = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }

    public function createOrder(array $data): array
    {
        $stmt = $this->db->prepare("
            INSERT INTO orders (name, email, phone, event_type, date, time, guests, reminder_sent)
            VALUES (:name, :email, :phone, :event_type, :date, :time, :guests, FALSE)
        ");
        
        $stmt->execute([
            ':name' => $data['name'] ?? '',
            ':email' => $data['email'] ?? '',
            ':phone' => $data['phone'] ?? '',
            ':event_type' => $data['event_type'] ?? '',
            ':date' => $data['date'] ?? '',
            ':time' => $data['time'] ?? '',
            ':guests' => (int)($data['guests'] ?? 1),
        ]);
        
        $orderId = (int)$this->db->lastInsertId();
        
        return $this->getOrderById($orderId);
    }

    public function getOrders(): array
    {
        $stmt = $this->db->query("SELECT * FROM orders ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public function getOrderById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $order = $stmt->fetch();
        return $order ?: null;
    }

    public function markReminderSent(int $orderId): bool
    {
        $stmt = $this->db->prepare("UPDATE orders SET reminder_sent = TRUE WHERE id = :id");
        return $stmt->execute([':id' => $orderId]);
    }

    public function getOrdersPendingReminder(): array
    {
        $stmt = $this->db->query("
            SELECT * FROM orders 
            WHERE reminder_sent = FALSE 
            AND TIMESTAMPDIFF(HOUR, created_at, NOW()) >= 24
            ORDER BY created_at ASC
        ");
        return $stmt->fetchAll();
    }
}
