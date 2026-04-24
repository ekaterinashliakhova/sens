# Sensorium Backend

Бэкенд для чат-бота музея «Сенсориум» с интеграцией GigaChat и RAG.

## Установка

1. Скопируйте `.env.example` в `.env` и заполните credentials:
```bash
cp .env.example .env
```

2. Установите зависимости (требуется PHP 8.1+):
```bash
composer install
```

## API Endpoints

### Chat (RAG + GigaChat)
**POST /api/chat**
```json
{
    "message": "Какие у вас есть экскурсии?"
}
```

### Orders
**POST /api/orders** - Создать заказ
```json
{
    "name": "Иван Иванов",
    "email": "ivan@example.com",
    "phone": "+79990000000",
    "event_type": "Экскурсия",
    "date": "2024-05-01",
    "time": "15:00",
    "guests": 4
}
```

**GET /api/orders** - Получить все заказы

**GET /api/orders/{id}** - Получить заказ по ID

### Reminders
**POST /api/reminders/send** - Отправить напоминания (заказы старше 24 часов)

## Структура проекта

```
backend/
├── app/
│   ├── Http/Controllers/
│   │   ├── ChatController.php
│   │   ├── OrderController.php
│   │   └── ReminderController.php
│   ├── Models/
│   │   └── Order.php
│   └── Services/
│       ├── QaService.php      # RAG из файла QA
│       ├── GigaChatService.php # Интеграция с GigaChat
│       ├── OrderService.php   # Управление заказами
│       └── EmailService.php   # Отправка email
├── storage/
│   ├── qa/QA                  # Файл с вопросами и ответами
│   └── orders.json            # Хранилище заказов
├── public/
│   └── index.php              # Точка входа API
└── .env                       # Конфигурация
```

## Запуск сервера

```bash
php -S localhost:8000 -t public
```

## Cron для напоминаний

Добавьте в crontab для отправки напоминаний каждый час:
```bash
0 * * * * curl -X POST http://localhost:8000/api/reminders/send
```
