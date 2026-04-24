<?php

/**
 * Simple Router for Sensorium Backend
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Http\Controllers\ChatController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReminderController;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Load environment variables from .env file if exists
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $envVars = parse_ini_file($envFile);
    foreach ($envVars as $key => $value) {
        $_ENV[$key] = $value;
    }
}

try {
    switch (true) {
        // Chat endpoint
        case preg_match('#^/api/chat$#', $uri):
            if ($method !== 'POST') {
                throw new Exception('Method not allowed', 405);
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $controller = new ChatController();
            $response = $controller->sendMessage($input ?? []);
            break;

        // Orders endpoints
        case preg_match('#^/api/orders$#', $uri):
            if ($method === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                $controller = new OrderController();
                $response = $controller->createOrder($input ?? []);
            } elseif ($method === 'GET') {
                $controller = new OrderController();
                $response = $controller->getOrders();
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;

        case preg_match('#^/api/orders/(\d+)$#', $uri, $matches):
            if ($method !== 'GET') {
                throw new Exception('Method not allowed', 405);
            }
            
            $orderId = (int)$matches[1];
            $controller = new OrderController();
            $response = $controller->getOrder($orderId);
            break;

        // Reminder endpoint
        case preg_match('#^/api/reminders/send$#', $uri):
            if ($method !== 'POST') {
                throw new Exception('Method not allowed', 405);
            }
            
            $controller = new ReminderController();
            $response = $controller->sendReminders();
            break;

        default:
            throw new Exception('Not found', 404);
    }

    http_response_code(200);
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
