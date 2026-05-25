# Lead & Call CRM — Laravel API

Невеликий API-сервіс для управління лідами та дзвінками в CRM-системі.

## Встановлення

```bash
git clone <repo-url>
cd laravel-lead-crm
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

## Запуск

```bash
php artisan serve
```

## API Ендпоінти

| Метод | URL | Опис |
|-------|-----|------|
| `POST` | `/api/leads` | Створення ліда (`name`, `phone`) |
| `POST` | `/api/leads/{lead}/calls` | Додавання дзвінка (`duration`, `result`, `manager_id`) |
| `GET` | `/api/managers/{manager}/leads` | Список лідів менеджера з `calls_count` та `total_call_duration` |

### Формат відповідей

Усі відповіді мають єдиний формат завдяки `App\Support\ApiResponse`:

```json
// Успіх
{ "success": true, "data": { ... } }

// Помилка
{ "success": false, "message": "...", "errors": { ... } }
```

## Архітектура

### Enums (`app/Enums/`)
- **`LeadStatus`** — `new`, `in_progress`, `won`, `lost`
- **`CallResult`** — `no_answer`, `callback_later`, `success`

PHP 8.1 backed enums замість magic-рядків. Використовуються в Eloquent casts, Observer, та Form Request валідації (`Rule::enum()`).

### Бізнес-логіка (`app/Observers/CallObserver.php`)
Вся логіка переходу статусів зосереджена в Observer і виконується автоматично при створенні запису `Call` (подія `created`):

- Перший дзвінок → `new` → `in_progress`
- Якщо у ліда немає менеджера → автоматичне призначення
- `result = success` → статус `won`
- Останні 3 дзвінки підряд `no_answer` → статус `lost`

Статуси **не можуть** бути змінені напряму через API — поле `status` виключено з `$fillable`.

### Exceptions (`app/Exceptions/`)
- **`BaseException`** (`app/Support/`) — абстрактний клас з автоматичним JSON-рендерингом через `ApiResponse`
- **`LeadNotFoundException`** — 404
- **`ManagerNotFoundException`** — 404
- **`InvalidCallException`** — 422

Глобальна обробка `NotFoundHttpException` та `ValidationException` зареєстрована в `bootstrap/app.php`.

### Support (`app/Support/`)
- **`ApiResponse`** — хелпер для стандартизованих JSON-відповідей (`success`, `created`, `error`)
- **`BaseException`** — базовий клас для доменних виключень

### Валідація
Реалізована через **Form Request** класи (`StoreLeadRequest`, `StoreCallRequest`), що відокремлює її від контролерів.

## Що можна було б покращити

- **API Resources** — `JsonResource` / `ResourceCollection` для гнучкого контролю серіалізації
- **Service Layer** — винести бізнес-логіку з Observer у сервісний клас для кращої тестованості
- **Пагінація** — для роботи з великими обсягами даних
- **Events** — Laravel Events замість Observer для асинхронної обробки
- **Авторизація** — Laravel Sanctum для захисту API
- **Фільтрація та сортування** — query-параметри для фільтрації лідів

## Тести

```bash
php artisan test
```

20 feature-тестів покривають усі ендпоінти, валідацію, 404-обробку та правила бізнес-логіки.
