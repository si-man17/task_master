# Образ-инструмент: запускается только для установки зависимостей,
# в обычном `docker compose up` не участвует (profiles: [tools]).
FROM composer:2.8

# Composer в контейнере работает от root и ругается на это предупреждением.
ENV COMPOSER_ALLOW_SUPERUSER=1

# Целевая платформа (php 8.2, ext-sockets) зафиксирована в composer.json -> config.platform,
# поэтому отключать проверку платформенных требований не нужно.

# Кэш пакетов в фиксированном пути, чтобы вынести его в именованный том compose.
ENV COMPOSER_CACHE_DIR=/tmp/composer-cache

# Совпадает с путём монтирования проекта в сервисе app.
WORKDIR /var/www

ENTRYPOINT ["composer"]
CMD ["install"]
