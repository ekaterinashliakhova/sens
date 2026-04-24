# Sensorium — Документация для разработчика

## Обзор проекта

Sensorium — это интерактивный чат-бот для продажи билетов на мероприятия в темноте. Проект состоит из фронтенда (HTML/CSS/JS) и бэкенда (PHP/Laravel).

## Структура проекта

```
/workspace
├── index.html              # Главная страница с чат-ботом
├── style.css               # Все стили приложения
├── script.js               # Логика чат-бота
├── lena-avatar.jpg         # Аватар бота
├── README.md               # Общая документация
├── USER_GUIDE.md           # Руководство пользователя
└── backend/                # Бэкенд на PHP
    ├── app/
    │   ├── Http/Controllers/
    │   ├── Models/
    │   └── Services/
    ├── public/
    │   └── index.php       # Точка входа API
    └── storage/
        ├── qa/             # База знаний QA
        └── orders.json     # Хранилище заказов
```

## Подключение к базам данных

### 1. SQLite/JSON хранилище (бэкенд)

**Расположение:** `/workspace/backend/storage/orders.json`

**Использование:**
```php
// Чтение заказов
$orders = json_decode(file_get_contents(storage_path('orders.json')), true);

// Запись заказа
file_put_contents(
    storage_path('orders.json'),
    json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);
```

**Структура заказа:**
```json
{
    "id": 1,
    "name": "Иван Иванов",
    "email": "ivan@example.com",
    "phone": "+79990000000",
    "event_type": "Экскурсия",
    "date": "2024-05-01",
    "time": "15:00",
    "guests": 4,
    "created_at": "2024-05-01T10:00:00Z"
}
```

### 2. База знаний QA

**Расположение:** `/workspace/backend/storage/qa/QA`

**Формат:** Текстовый файл с вопросами и ответами для RAG-системы.

**Использование в коде:**
```php
// QaService.php
$qaContent = file_get_contents(storage_path('qa/QA'));
// Поиск релевантных ответов через векторный поиск или keyword matching
```

## Интеграция с GigaChat

### Настройка

1. **Получите credentials:**
   - Зарегистрируйтесь в [GigaChat Developers](https://developers.sber.ru/gigachat)
   - Получите `GIGACHAT_CLIENT_ID` и `GIGACHAT_CLIENT_SECRET`

2. **Настройте .env:**
```bash
cd /workspace/backend
cp .env.example .env
# Заполните:
GIGACHAT_CLIENT_ID=your_client_id
GIGACHAT_CLIENT_SECRET=your_client_secret
GIGACHAT_SCOPE=GIGACHAT_API_PERS
```

### Использование в коде

**GigaChatService.php:**
```php
<?php

namespace App\Services;

use GuzzleHttp\Client;

class GigaChatService
{
    private $client;
    private $accessToken;
    
    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://ngw.devices.sberbank.ru:9443/api/v2/',
        ]);
    }
    
    // Получение токена доступа
    private function getAccessToken()
    {
        $response = $this->client->post('token', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode(
                    env('GIGACHAT_CLIENT_ID') . ':' . env('GIGACHAT_CLIENT_SECRET')
                ),
                'RqUID' => uniqid(),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'form_params' => [
                'scope' => env('GIGACHAT_SCOPE'),
            ],
        ]);
        
        $data = json_decode($response->getBody(), true);
        return $data['access_token'];
    }
    
    // Отправка запроса к GigaChat
    public function chat($message, $context = '')
    {
        if (!$this->accessToken) {
            $this->accessToken = $this->getAccessToken();
        }
        
        $response = $this->client->post('chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'GigaChat',
                'messages' => [
                    ['role' => 'system', 'content' => 'Ты — гид музея «Сенсориум». Отвечай дружелюбно и подробно.'],
                    ['role' => 'user', 'content' => $context . "\n\nВопрос: " . $message],
                ],
            ],
        ]);
        
        $data = json_decode($response->getBody(), true);
        return $data['choices'][0]['message']['content'];
    }
}
```

### RAG (Retrieval-Augmented Generation)

**Принцип работы:**
1. Пользователь задаёт вопрос
2. Ищем релевантные фрагменты в базе знаний QA
3. Добавляем найденный контекст к запросу
4. Отправляем в GigaChat с контекстом
5. Возвращаем ответ пользователю

**ChatController.php:**
```php
public function chat(Request $request)
{
    $message = $request->input('message');
    
    // Поиск в базе знаний
    $qaService = new QaService();
    $context = $qaService->findRelevant($message);
    
    // Запрос к GigaChat с контекстом
    $gigaChat = new GigaChatService();
    $response = $gigaChat->chat($message, $context);
    
    return response()->json(['reply' => $response]);
}
```

## API Endpoints

### Chat (RAG + GigaChat)
```http
POST /api/chat
Content-Type: application/json

{
    "message": "Какие у вас есть экскурсии?"
}

Response:
{
    "reply": "У нас есть интерактивная экскурсия по 5 пространствам..."
}
```

### Orders
```http
# Создать заказ
POST /api/orders
Content-Type: application/json

{
    "name": "Иван Иванов",
    "email": "ivan@example.com",
    "phone": "+79990000000",
    "event_type": "Экскурсия",
    "date": "2024-05-01",
    "time": "15:00",
    "guests": 4
}

# Получить все заказы
GET /api/orders

# Получить заказ по ID
GET /api/orders/{id}
```

### Reminders
```http
# Отправить напоминания (заказы старше 24 часов)
POST /api/reminders/send
```

## Фронтенд: Работа с данными

### Отправка заказа на бэкенд

**script.js:**
```javascript
async function submitOrder(orderData) {
  try {
    const response = await fetch('/backend/public/index.php/api/orders', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(orderData),
    });
    
    const result = await response.json();
    
    if (result.success) {
      addBotMessage('✅ Ваш заказ успешно оформлен! Confirmation отправлена на email.');
    } else {
      addBotMessage('❌ Произошла ошибка при оформлении заказа. Попробуйте ещё раз.');
    }
  } catch (error) {
    console.error('Error:', error);
    addBotMessage('❌ Ошибка соединения с сервером.');
  }
}
```

### Получение ответов от чат-бота

```javascript
async function sendToGigaChat(message) {
  showTyping();
  
  try {
    const response = await fetch('/backend/public/index.php/api/chat', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ message }),
    });
    
    const data = await response.json();
    hideTyping();
    addBotMessage(data.reply);
  } catch (error) {
    hideTyping();
    addBotMessage('Извините, возникла проблема с соединением. Попробуйте позже.');
  }
}
```

## Локальная разработка

### Фронтенд
```bash
cd /workspace
python -m http.server 8000
# Откройте http://localhost:8000
```

### Бэкенд
```bash
cd /workspace/backend
composer install
php -S localhost:8001 -t public
# API доступен на http://localhost:8001
```

### CORS настройка (если нужно)

**backend/public/index.php:**
```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
```

## Тестирование

### Проверка подключения к GigaChat
```bash
curl -X POST http://localhost:8001/api/chat \
  -H "Content-Type: application/json" \
  -d '{"message": "Привет! Какие у вас есть мероприятия?"}'
```

### Проверка создания заказа
```bash
curl -X POST http://localhost:8001/api/orders \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Тест Тестов",
    "email": "test@example.com",
    "phone": "+79990000000",
    "event_type": "Экскурсия",
    "date": "2024-05-01",
    "time": "15:00",
    "guests": 2
  }'
```

## Развёртывание

### Требования
- PHP 8.1+
- Composer
- Веб-сервер (Apache/Nginx) или встроенный PHP server
- Доступ к интернету для GigaChat API

### Production настройка

1. **Бэкенд:**
```bash
cd /workspace/backend
composer install --no-dev --optimize-autoloader
cp .env.example .env
# Заполните credentials
```

2. **Настройте веб-сервер:**
```nginx
server {
    listen 80;
    server_name sensorium.example.com;
    root /workspace/backend/public;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

3. **Cron для напоминаний:**
```bash
# /etc/crontab
0 * * * * curl -X POST http://localhost:8001/api/reminders/send
```

## Безопасность

1. **Храните credentials в .env** (не коммитьте в git!)
2. **Валидируйте все входные данные** на бэкенде
3. **Используйте HTTPS** в production
4. **Ограничьте rate limiting** для API endpoints
5. **Санитизируйте вывод** для предотвращения XSS

## Отладка

### Логи бэкенда
```bash
tail -f /workspace/backend/storage/logs/laravel.log
```

### Консоль браузера
```javascript
// В script.js добавьте console.log для отладки
console.log('Order data:', orderData);
```

### Проверка токена GigaChat
```php
// Временно выведите токен для проверки
error_log('GigaChat Token: ' . $this->accessToken);
```

## Контакты и поддержка

По вопросам интеграции и настройки обращайтесь к технической документации:
- [GigaChat API Docs](https://developers.sber.ru/docs/ru/gigachat)
- [Laravel Documentation](https://laravel.com/docs)

---

**Версия документации:** 1.0  
**Дата обновления:** 2024
