# Трекер задач проекта «тест канбан»

Статусы: `[ ]` — не начата · `[~]` — в работе · `[R]` — на ревью у Claude · `[x]` — готово (смержено в develop)

Правила ведения:
- Обновляется при каждом рабочем сеансе (студентом или Claude после ревью).
- Задача считается `[x]` только после ревью и мержа в develop.
- Новые задачи добавляются в конец своего этапа, не переписывая историю.

---

## Этап 0. Инфраструктура — ТЕКУЩИЙ ФОКУС

- [x] Репозиторий, ветки main/develop, SSH
- [x] docker-compose.yaml: RoadRunner + MySQL 8.0 + phpMyAdmin
- [x] .rr.yaml: static, CORS, pool воркеров
- [x] composer.json: roadrunner, psr7, phpdotenv, phpunit
- [~] Заполнить .gitignore (vendor/, .env, node_modules/, .idea/ и т.д.)
- [~] scripts/init.sql — таблица tasks (id, title, description, status, position, created_at)
- [~] worker.php — минимальный цикл RoadRunner, ответ {"status":"ok"}
- [~] composer install через dockerfiles/composer.Dockerfile
- [~] Проверка: docker compose up → :8000 отвечает, phpMyAdmin (:1500) видит БД
- [ ] Коммит `feat: project infrastructure (roadrunner + mysql)` в feature/docker-setup, PR → develop

## Этап 1. MVP — CRUD канбана (чистый PHP + vanilla JS)

- [ ] Роутер в src/ (без библиотек): разбор метода и пути, 404
- [ ] Слои: Router → Controller → Repository (PDO), подключение из env
- [ ] GET /api/tasks — список задач
- [ ] POST /api/tasks — создание
- [ ] PATCH /api/tasks/{id} — обновление (статус, позиция, текст)
- [ ] DELETE /api/tasks/{id} — удаление
- [ ] public/index.html — доска: 3 колонки (To Do / In Progress / Done)
- [ ] Нативный Drag&Drop + fetch: перетащил → PATCH
- [ ] Форма создания и удаление карточки в UI
- [ ] Ветки feature/api-crud, feature/frontend-board → PR → develop, тег v0.1

## Этап 2. Пользователи, middleware, real-time

- [ ] Таблица users + миграция init.sql
- [ ] Регистрация/логин, Bearer-токен (UUID в БД, без JWT)
- [ ] Цепочка middleware: cors, json-body, auth
- [ ] Redis в compose
- [ ] WS-сервис: подписка на Redis pub/sub, рассылка клиентам
- [ ] API публикует события изменений в Redis
- [ ] Фронт: подключение к WS, живое обновление доски
- [ ] PHPUnit-тесты на роутер и репозиторий (сгенерированы с ИИ, проверены руками)
- [ ] Ветки feature/auth, feature/websockets → PR → develop

## Этап 3. Асинхронность, nginx, первый React

- [ ] RabbitMQ в compose (+ management UI)
- [ ] Публикация «task.created» в очередь из API
- [ ] Контейнер php-worker: консюмер, «отправка письма» (лог/MailHog), ack/retry
- [ ] nginx как reverse proxy: статика, /api, /ws (upgrade), буферизация
- [ ] /frontend-react (Vite): доска на React
- [ ] nginx: старый HTML на /old, React на / (Strangler)
- [ ] pre-commit хук: PHP CS Fixer + тесты
- [ ] AI-фича: «AI-описание задачи» — кнопка → очередь → консюмер → Claude API → результат по WS
- [ ] Обработка отказов LLM: таймаут, ретраи, лимит частоты, ключ в .env
- [ ] commit-msg хук: ИИ-проверка conventional commits
- [ ] Ветки feature/rabbitmq, feature/nginx-proxy, feature/react-board, feature/ai-description

## Этап 4. Laravel API v2 + Kafka

- [ ] /laravel-api: Laravel API-only в своём контейнере
- [ ] nginx: /api/v2/* → Laravel, /api/v1/* → старый код
- [ ] Миграции и Eloquent-модели (tasks, users)
- [ ] CRUD на v2, авторизация (Sanctum)
- [ ] Письма и AI-вызовы через Laravel Queues
- [ ] Kafka в compose; событие TaskCreated → топик
- [ ] Консюмер-сервис читает топик (задел под поиск)
- [ ] React переведён на /api/v2 эндпоинт за эндпоинтом
- [ ] AI: structured output — «разбей эпик на подзадачи» (строгий JSON + валидация схемы)

## Этап 5. Микросервисы и смерть монолита

- [ ] Auth-сервис отдельным контейнером
- [ ] API Gateway: вся маршрутизация через него
- [ ] CDC: Debezium → Kafka → сервис статистики → Redis/Elasticsearch
- [ ] CI/CD: GitHub Actions — тесты, сборка образов, деплой
- [ ] MCP-сервер канбана: ИИ-агент читает/создаёт задачи
- [ ] AI-суммаризация «неделя на доске» по событиям Kafka
- [ ] (опц.) embeddings-поиск по задачам
- [ ] Финал: chore: remove old public/index.php (monolith is dead)

---

## Журнал сеансов

| Дата | Что сделано |
|------|-------------|
| 2026-08-21 | Репозиторий, ветки main/develop |
| 2026-08-24 | docker-compose, .rr.yaml, composer.json (не закоммичено) |
| 2026-08-25 | CLAUDE.md, task.md, план утверждён. Фокус — добить Этап 0 |
| 2026-09-07 | Ревью .rr.yaml (uploads, headers, relay_timeout), composer.Dockerfile, composer.json (config.platform, ext-*), init.sql, compose (env MYSQL_*, кэш composer). .env снят с отслеживания git (был закоммичен и запушен в origin/develop) |
| 2026-09-08 | Диагностика перезапусков app: нет vendor, путь autoload, spiral/roadrunner — метапакет без кода → заменён на spiral/roadrunner-http. RR поднят до 2025.1 (CVE-2025-22871). worker.php написан, curl :8000 → {"status":"ok"}, таблица tasks в БД. Осталось: коммит в feature/docker-setup, PR |
