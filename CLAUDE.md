# Kanban Task Tracker — учебный пет-проект («тест канбан»)

## Правила работы с Claude
- Это **учебный проект**: студент пишет весь код САМ, Claude только направляет, объясняет и проверяет.
- Claude НЕ вносит правки в код без явной просьбы. Разрешено: обновлять этот CLAUDE.md, отвечать на вопросы, ревьюить код, давать подсказки.
- Цель — прокачать стек: PHP, JS, MySQL, Docker, git, nginx, WebSockets, RabbitMQ, Kafka, затем Laravel + React.
- Вторая цель — **прокачать навыки работы с ИИ** (см. трек ниже): и как инструмент разработки, и как фичу продукта.
- Язык общения: русский.

## Архитектура (текущая)
- SPA + API. HTTP-сервер — **RoadRunner** (вместо классического nginx+php-fpm; nginx появится на этапе 3 как reverse proxy).
- PHP >= 8.2, PSR-7 (nyholm/psr7), автозагрузка PSR-4: `App\` → `src/`.
- MySQL 8.0 в Docker (порт хоста 3316), phpMyAdmin на :1500, приложение на :8000 (внутри 8080).
- Точка входа PHP-воркера: `worker.php` — цикл PSR7Worker (spiral/roadrunner-http), отвечает `{"status":"ok"}`. Статика раздаётся RoadRunner из `public/`.
- Пакет `spiral/roadrunner` — метапакет без PHP-кода, классы Worker/PSR7Worker берутся из `spiral/roadrunner-http`. Бинарник rr в образе: `ghcr.io/roadrunner-server/roadrunner:2025.1` (multi-stage COPY в app.Dockerfile).
- `.env` ключи `MYSQL_*` (их ждёт образ mysql); compose прокидывает их в app как `DB_*`.

## Структура
```
docker-compose.yaml   # app (RoadRunner), mysql8.0, phpmyadmin
.rr.yaml              # конфиг RoadRunner: static, gzip, pool 4 воркера (headers/CORS — на этапе 3)
composer.json         # roadrunner-http, psr7, phpdotenv, phpunit; config.platform = php 8.2
dockerfiles/          # app.Dockerfile (php:8.2-cli + rr), composer.Dockerfile (profile tools)
public/               # фронтенд (index.html + js) — пока пусто
src/                  # PHP-код (App\) — пока пусто
scripts/              # init.sql — таблица tasks
worker.php            # цикл RoadRunner-воркера — готов
```

## Git
- Ветки: `main` (prod), `develop` (рабочая), `feature/*` — от develop, мерж через PR.
- Коммиты в стиле conventional commits: `feat:`, `fix:`, `refactor:`, `chore:`.
- Сейчас: 3 коммита в develop, вся инфраструктура Этапа 0 в рабочей копии, НЕ закоммичена. `.env` был закоммичен и запушен в origin/develop — снят с отслеживания (`git rm --cached`), история не переписана.

## Roadmap (полный план — не переписывать с нуля, паттерн Strangler Fig)

### Этап 0. Инфраструктура — В ПРОЦЕССЕ
Сделано: всё, кроме коммита. `docker compose up` работает, :8000 отвечает `{"status":"ok"}`, таблица tasks создаётся из init.sql.
Осталось:
- [x] Заполнить `.gitignore` (vendor/, .env, node_modules/, .idea/ и т.д.)
- [x] Написать `scripts/init.sql` (таблица tasks: id, title, description, status, position, created_at)
- [x] Написать `worker.php` — минимальный цикл RoadRunner (PSR-7 Worker), отвечающий "hello"
- [x] `composer install` через dockerfiles/composer.Dockerfile
- [x] Убедиться: `docker compose up` → :8000 отвечает, phpMyAdmin видит БД
- [ ] Коммит `feat: project infrastructure (roadrunner + mysql)` в `feature/docker-setup`, PR в develop
Чекпоинт: контейнеры поднимаются, воркер отвечает на HTTP.

### Этап 1. MVP — CRUD канбана (чистый PHP + vanilla JS)
- Роутер в `src/` (без библиотек): GET /api/tasks, POST /api/tasks, PATCH /api/tasks/{id}, DELETE /api/tasks/{id}
- Слои: Router → Controller → Repository (PDO). Подключение к БД из env-переменных.
- `public/index.html`: доска 3 колонки (To Do / In Progress / Done), нативный Drag&Drop API, fetch к API.
- Ветки: `feature/api-crud`, `feature/frontend-board`.
Чекпоинт: карточку можно создать, перетащить между колонками, статус сохраняется в MySQL.

### Этап 2. Пользователи, middleware, real-time (WebSockets)
- Таблица users, авторизация по Bearer-токену (UUID в БД, без JWT пока).
- Цепочка middleware в своём роутере (идея из Laravel): auth, json-body, cors.
- WebSockets: отдельный WS-сервис + Redis pub/sub — при изменении задачи все открытые доски обновляются без перезагрузки.
- Ветки: `feature/auth`, `feature/websockets`.
Чекпоинт: два браузера видят перемещение карточки друг друга в реальном времени.

### Этап 3. Асинхронность и nginx (RabbitMQ + первый React)
- RabbitMQ в compose. Событие "задача создана" → сообщение в очередь → отдельный контейнер php-worker (консюмер) "шлёт письмо" (можно в лог/MailHog).
- **nginx как reverse proxy** перед RoadRunner: статика, проксирование /api и /ws, буферизация. Вот здесь nginx входит в стек.
- Первая React-страница в `/frontend-react` (Vite). nginx: старый HTML на /old, React на /. Начало Strangler.
- Git hooks: pre-commit → PHP CS Fixer + тесты.
- Ветки: `feature/rabbitmq`, `feature/nginx-proxy`, `feature/react-board`.
Чекпоинт: создание задачи кладёт сообщение в очередь, консюмер обрабатывает; React-доска работает через nginx.

### Этап 4. Laravel API v2 + Kafka
- Laravel (API-only) в `/laravel-api`, свой контейнер. nginx: /api/v2/* → Laravel, /api/v1/* → старый код. Параллельная жизнь.
- CRUD переписывается на Eloquent, письма — через Laravel Queues.
- Kafka в compose: событие TaskCreated публикуется в топик, отдельный консюмер-сервис читает (например, индексация для поиска).
Чекпоинт: фронт ходит в /api/v2, старый монолит ещё жив, но не используется.

### Этап 5. Микросервисы и выпиливание монолита
- Отдельный auth-сервис; API Gateway; Read Models через CDC (Debezium → Kafka → Redis/Elasticsearch).
- CI/CD: GitHub Actions — тесты (PHPUnit+Jest), сборка образов, деплой.
- Финальный коммит: `chore: remove old public/index.php (monolith is dead)`.

## Трек: навыки работы с ИИ (параллельно основным этапам)

Два направления: (А) ИИ как инструмент разработчика, (Б) ИИ как фича продукта.

### Этап 0–1 (А): базовая культура работы с ИИ
- Контекст-инжиниринг: вести этот CLAUDE.md как «память» проекта — учиться формулировать контекст так, чтобы новая сессия ИИ сразу входила в курс дела.
- Промптинг: просить у ИИ не готовый код, а объяснения, ревью и наводящие вопросы; сравнивать свои решения с предложениями ИИ и разбирать различия.
- Ревью через ИИ: каждый PR перед мержем прогонять через ревью Claude (найденные проблемы чинить самому).
Чекпоинт: есть привычка — написал код → ревью ИИ → осознанные исправления → коммит.

### Этап 2–3 (А): ИИ в цикле разработки
- Генерация тестов: писать PHPUnit-тесты к своему коду с помощью ИИ, но проверять, что тесты осмысленные (ловят реальные баги, а не просто зеленые).
- Отладка с ИИ: учиться давать ИИ минимальный воспроизводимый контекст (лог, конфиг, кусок кода), а не «ничего не работает».
- ИИ-ассистент в git-хуках: например, pre-commit / commit-msg хук, который проверяет сообщение коммита на соответствие conventional commits.

### Этап 3–4 (Б): первая AI-фича в продукте — через очередь
Вызов LLM — идеальная «тяжёлая фоновая операция» для RabbitMQ (долгая, может упасть, нельзя блокировать HTTP-запрос):
- [ ] Фича: «AI-описание задачи» — пользователь пишет заголовок, жмёт кнопку → задание уходит в очередь → консюмер зовёт Claude API → описание появляется в карточке (доставка через WebSocket).
- Изучить: Claude API (messages, модели, стоимость токенов), хранение API-ключа в .env, обработка ошибок/таймаутов/ретраев LLM-вызова, ограничение частоты.
- Вариант посложнее: «AI-разбивка» — эпик разбивается LLM на подзадачи (structured output / JSON).
Чекпоинт: понимаю жизненный цикл LLM-запроса в проде: очередь → вызов → ретраи → доставка результата.

### Этап 4–5 (Б): продвинутая интеграция
- Structured outputs / tool use: LLM возвращает строгий JSON (например, классификация приоритета задачи), бэкенд валидирует схему.
- MCP-сервер для канбана: написать простой MCP-сервер, чтобы любой ИИ-агент мог читать/создавать задачи на доске — так проект сам становится инструментом для ИИ.
- Суммаризация: «что произошло на доске за неделю» — LLM-отчёт по событиям из Kafka.
- Оценить embeddings-поиск по задачам (вместе с Elasticsearch на этапе 5).
Чекпоинт: канбан доступен ИИ-агенту через MCP, есть хотя бы одна фича на structured output.

## Полезное
- **Трекер задач: `task.md`** — статус каждой задачи по этапам + журнал сеансов. Обновлять при каждом сеансе; задача «готова» только после ревью и мержа в develop.
- Полный исходный план с пояснениями: `task.txt`
- env-пример: `config.env.example`; реальный `.env` не коммитить.
- MySQL с хоста: localhost:3316; изнутри docker-сети: mysql8.0:3306.

## Журнал прогресса
- 2026-08-21: репозиторий создан, ветки main/develop
- 2026-08-24: docker-compose, .rr.yaml, composer.json (не закоммичено)
- 2026-08-25: этот CLAUDE.md создан; текущий фокус — добить Этап 0
- 2026-09-08: Этап 0 работает end-to-end (RR 2025.1, worker.php, init.sql). Осталось: ветка feature/docker-setup, коммит, PR в develop. Полезный приём отладки: `docker compose run --rm --no-deps app php worker.php` показывает ошибку старта воркера без шума RR.
