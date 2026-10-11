# Импорт товаров из Excel: Laravel + Angular

Приложение загружает товары из `.xlsx` файла в реляционную БД и показывает их в веб-интерфейсе:

1. **Импорт товаров**: загрузка `.xlsx`. Файл обрабатывается асинхронно через очередь RabbitMQ, на странице видны прогресс и отчёт об ошибках.
2. **Список товаров**: серверная пагинация, поиск по наименованию, фильтр по диапазону цены.
3. **Карточка товара**: все поля, дополнительные атрибуты и галерея скачанных изображений.

| Слой | Технологии |
|------|-----------|
| Backend | PHP 8.4, **Laravel 13**, Eloquent ORM, Sanctum (Bearer-токены), OpenSpout (потоковое чтение xlsx) |
| Очередь | Laravel Queue + **RabbitMQ** (`vladimir-yuldashev/laravel-queue-rabbitmq`) |
| БД | **PostgreSQL 17** |
| Frontend | **Angular 21** (standalone, lazy routes), **NgRx Store + Effects**, Angular Material, TypeScript strict |
| Инфраструктура | Docker Compose (multi-stage), nginx, GitHub Actions |
| Качество | PHPUnit, Vitest, Playwright, PHPStan (Larastan, level 6), PHP CS Fixer, Prettier |

> **Отступления от исходного ТЗ.** В ТЗ указаны Slim + Doctrine и Symfony Messenger. Backend по требованию
> сделан на **Laravel**, поэтому использованы их аналоги: Eloquent вместо Doctrine и
> Laravel Queue с драйвером RabbitMQ вместо Symfony Messenger. Архитектурные требования (Service-слой,
> Repository-паттерн, тонкие контроллеры, транзакции) выполнены в полном объёме.

---

## Быстрый старт

Нужен только **Docker** (с Compose v2).

```bash
make setup        # создаёт .env, генерирует APP_KEY, собирает образы и поднимает стек
```

Без `make`: `./deploy.sh` (Linux/macOS/Git Bash) или `.\deploy.ps1` (Windows PowerShell).

| Что | Адрес |
|-----|-------|
| Приложение | http://localhost:8080 (логин `admin@example.com` / `password`) |
| Swagger UI | http://localhost:8080/docs |
| OpenAPI спецификация | http://localhost:8080/docs/openapi.yaml |
| Health check | http://localhost:8080/api/health |
| RabbitMQ management | http://localhost:15672 (`guest` / `guest`) |

Пример файла для импорта лежит в [backend/tests/Fixtures/import_example.xlsx](backend/tests/Fixtures/import_example.xlsx).

### Основные команды

```bash
make help         # список всех команд
make up / down    # запустить / остановить стек
make logs         # логи app и worker
make seed         # демо-данные (фикстуры) для products, product_attributes, product_images
make fresh        # пересоздать БД с сидерами
make test         # backend тесты: unit + feature (CRUD, импорт) + API e2e
make lint         # PHPStan + PHP CS Fixer
make front-test   # frontend unit тесты (Vitest)
make e2e          # браузерные e2e тесты (Playwright) против запущенного стека
make clean        # остановить и удалить volumes
```

### Локальная разработка frontend

```bash
make up                      # backend в docker
cd frontend && npm install
npm start                    # http://localhost:4200, /api и /storage проксируются на :8080
```

---

## Docker-сервисы

| Сервис | Назначение |
|--------|-----------|
| `app` | Laravel (php-fpm). При старте выполняет миграции и создаёт администратора |
| `worker` | `php artisan queue:work rabbitmq --queue=imports`: обрабатывает импорты |
| `nginx` | Раздаёт собранный Angular SPA, проксирует `/api` и `/docs` в php-fpm, раздаёт скачанные изображения `/storage` |
| `db` | PostgreSQL |
| `rabbitmq` | Брокер сообщений (с management UI) |

Образы собираются в несколько стадий (multi-stage):
- [backend/Dockerfile](backend/Dockerfile): `base` (php-fpm + расширения), `dev` (dev-зависимости для тестов и линтеров), `vendor` (composer install без dev-пакетов), `production`;
- [docker/nginx/Dockerfile](docker/nginx/Dockerfile): сборка Angular в `node:22`, затем `nginx:alpine`.

Общие volumes: `uploads` (загруженные xlsx, пишет `app`, читает `worker`) и `media` (изображения, пишет `worker`, отдаёт `nginx`).
Все переменные окружения с описанием собраны в [.env.example](.env.example).

---

## Как работает импорт

```
Angular ──POST /api/imports (xlsx)──▶ ImportController ──▶ ImportService
                                            │  валидация файла, сохранение,
                                            │  запись imports(status=pending)
   ◀── 202 {id, status} ────────────────────┘  dispatch(ProcessProductImport) ──▶ RabbitMQ
                                                                                     │
Angular ──GET /api/imports/{id} (polling 1с)──▶ прогресс и отчёт ◀── worker ◀────────┘
                                                      ProductImportService:
                                                      SpreadsheetReader → ProductRowParser →
                                                      ImageDownloader → транзакция upsert
```

- **Валидация файла** ([StoreImportRequest](backend/app/Http/Requests/StoreImportRequest.php)): расширение `.xlsx`, MIME-тип по содержимому файла, размер до 10 МБ. На frontend есть такая же предварительная проверка.
- **Rate limiting**: не больше 5 импортов в минуту на пользователя (`IMPORT_RATE_LIMIT_PER_MINUTE`), при превышении возвращается `429`.
- **Колонки ищутся по заголовку**, а не по позиции ([config/import.php](backend/config/import.php)):

  | Колонка xlsx | Поле |
  |---|---|
  | `Внешний код` | `products.external_code` (обязательное, уникальное) |
  | `Наименование` | `products.name` (обязательное) |
  | `Описание` | `products.description` |
  | `Цена: Цена продажи` | `products.price` (обязательное) |
  | `Закупочная цена` | `products.purchase_price` |
  | `Доп. поле: *` | `product_attributes` (key = текст после префикса, пустые значения пропускаются) |
  | `Доп. поле: Ссылки на фото`, `Доп. поле: Ссылка на упаковку` | `product_images` (скачиваются, в атрибуты не попадают) |

- **Скидка**: `discount = (Цена продажи − Закупочная цена) / Цена продажи × 100`, округление до 2 знаков. Если закупочной цены нет, скидка равна `null`.
- **Числа** вида `1 320,00`, `1.234,56`, `880.5` разбираются корректно ([NumberParser](backend/app/Support/NumberParser.php)).
- **Upsert по `external_code`**: при повторном импорте товар обновляется, а атрибуты и изображения заменяются. Дубликаты не создаются.
- **Атомарность**: товар, его атрибуты и изображения записываются в одной транзакции. Изображения скачиваются до транзакции, чтобы не держать её открытой во время сетевых запросов.
- **Ошибки не прерывают импорт**: невалидная строка (нет кода или названия, цена не число и т.п.) пропускается и попадает в `errors` с номером строки и списком причин. Некритичные проблемы попадают в `warnings`: недоступное изображение (товар сохраняется со ссылкой на оригинал) или повтор внешнего кода внутри файла. Если в файле нет обязательных колонок, весь импорт завершается со статусом `failed` и причиной.
- **Изображения** сохраняются под именем `sha1(url)`. Картинка, общая для нескольких товаров, скачивается один раз, при повторном импорте ничего не скачивается заново. Тип файла определяется по содержимому, не-изображения отклоняются. Если хост трижды подряд не ответил, остальные его ссылки пропускаются, чтобы мёртвый сервер не растягивал импорт.

---

## API

Полное описание: Swagger UI на `/docs` ([backend/resources/openapi/openapi.yaml](backend/resources/openapi/openapi.yaml)).
Все эндпоинты, кроме `health` и `login`, требуют заголовок `Authorization: Bearer <token>`.

| Метод | URL | Описание |
|-------|-----|----------|
| GET | `/api/health` | Проверка БД, брокера, хранилища (`200` / `503`) |
| POST | `/api/auth/login` | Выдача Bearer-токена |
| GET | `/api/auth/me` | Текущий пользователь |
| POST | `/api/auth/logout` | Отзыв токена |
| GET | `/api/products?page=1&limit=20&name=&price_min=&price_max=&sort=&direction=` | Список с серверной пагинацией: `{data, meta: {page, limit, total, total_pages}}` |
| GET | `/api/products/{id}` | Карточка с `attributes` и `images` |
| POST / PUT / DELETE | `/api/products[/{id}]` | CRUD (скидка пересчитывается автоматически) |
| POST | `/api/imports` | Загрузка xlsx, ответ `202` с задачей импорта |
| GET | `/api/imports/{id}` | Статус, прогресс (%), счётчики, `errors`, `warnings` |
| GET | `/api/imports` | Последние 10 импортов |

---

## Архитектура

### Backend ([backend/app](backend/app))

```
Http/Controllers/     тонкие контроллеры: принимают запрос, делегируют сервису, возвращают Resource
Http/Requests/        валидация входящих данных (файл, фильтры, CRUD)
Http/Resources/       JSON-представления (ProductResource, ProductCollection с meta пагинации, ImportResource)
Services/             ProductService (CRUD), AuthService, HealthCheckService
Services/Import/      ImportService (приём файла и постановка в очередь), ProductImportService (оркестрация),
                      SpreadsheetReader (OpenSpout), ProductRowParser (валидация и маппинг строки),
                      ImageDownloader
Repositories/         Contracts/* (интерфейсы) + Eloquent/* (реализации), биндинги в AppServiceProvider
Jobs/                 ProcessProductImport: задача очереди
Models/, DTO/, Enums/, Support/ (NumberParser, DiscountCalculator), Exceptions/
```

Таблицы: `products`, `product_attributes` (key/value, FK с каскадным удалением, уникальная пара product+key),
`product_images` (`url` исходная ссылка, `path` локальный путь, `position`), `imports` (статус, счётчики, отчёт в JSON).

### Frontend ([frontend/src/app](frontend/src/app))

```
models/               интерфейсы Product, ProductSummary, ProductAttribute, ProductImage, ImportJob, User
core/services/        ProductService, ImportService, AuthService: HttpClient только здесь
core/interceptors/    authInterceptor (Bearer-токен), errorInterceptor (401 → login, 429/5xx → уведомление)
core/guards/          authGuard (CanActivate), guestGuard
store/products/       NgRx: actions loadProducts / loadProductsSuccess / loadProductsFailure,
                      effect через ProductService, selectors selectAllProducts / selectProductsLoading / selectTotalPages
features/             login, products (список и карточка), import (lazy loaded standalone-компоненты)
app.routes.ts         роутинг; NgRx feature state регистрируется на lazy-маршруте /products
```

Страница списка работает через `store.select()` и `async` pipe, `.subscribe()` в ней нет. Номер страницы и фильтры
хранятся в URL (`?page=2&name=…`), поэтому работают кнопка «Назад» и ссылки на конкретную выборку.

---

## Тесты и качество кода

| Набор | Где | Что проверяет |
|-------|-----|---------------|
| Unit (PHPUnit) | `backend/tests/Unit` | разбор чисел, расчёт скидки, парсер строки, чтение xlsx |
| Feature (PHPUnit) | `backend/tests/Feature` | CRUD товаров, пагинация и фильтры, авторизация, валидация файла (тип, MIME, размер), rate limit, health, импорт примера (40 товаров), upsert, отчёт об ошибках, недоступные изображения, сидеры |
| API e2e (PHPUnit) | `backend/tests/E2E` | сценарий целиком через HTTP: логин → загрузка → статус → список → карточка → повторный импорт |
| Unit (Vitest) | `frontend/src/**/*.spec.ts` | reducer, selectors, effects, сервисы, guard, interceptor, компонент списка |
| Browser e2e (Playwright) | `frontend/e2e` | реальный браузер против docker-стека: guard, логин, загрузка примера, ожидание воркера, поиск, фильтр по цене, карточка |

- Сидеры и фабрики: [database/seeders](backend/database/seeders), [database/factories](backend/database/factories) (все три таблицы и администратор).
- PHPStan level 6: [phpstan.neon](backend/phpstan.neon). PHP CS Fixer: [.php-cs-fixer.php](backend/.php-cs-fixer.php).
- CI ([.github/workflows/ci.yml](.github/workflows/ci.yml)) на каждый `push`: линтеры и тесты backend, проверка форматирования, тесты и сборка frontend, сборка Docker-образов, запуск стека и Playwright e2e.
