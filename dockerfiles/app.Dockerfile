# Этап 1: берём готовый бинарник RoadRunner из официального образа
FROM ghcr.io/roadrunner-server/roadrunner:2025.1 AS roadrunner

# Этап 2: основной образ
FROM php:8.2-cli

# Системные зависимости и PHP-расширения
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libzip-dev \
    && docker-php-ext-install pdo_mysql sockets zip \
    && rm -rf /var/lib/apt/lists/*

# Бинарник rr из первого этапа
COPY --from=roadrunner /usr/bin/rr /usr/local/bin/rr

WORKDIR /var/www

# Код не копируем: он придёт через volume из compose
EXPOSE 8080

CMD ["rr", "serve", "-c", ".rr.yaml"]