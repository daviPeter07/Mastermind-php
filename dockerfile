FROM composer:2 as builder
WORKDIR /app
COPY . .
RUN composer install --no-interaction --optimize-autoloader

FROM php:8.3-cli
WORKDIR /app
COPY --from=builder /app .

RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]