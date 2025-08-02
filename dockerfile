# Dockerfile (sem Supervisor)

# Estágio 1: Builder com Composer
FROM composer:2 as builder
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --optimize-autoloader
COPY . .

# Estágio 2: Imagem Final com PHP
FROM php:8.3-cli
WORKDIR /app
COPY --from=builder /app .

# Instala apenas as dependências de sistema necessárias para o PHP
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Expõe a porta 8000 para a API
EXPOSE 8000

# O comando padrão será iniciar a API. O Render vai sobrescrever isso para o bot.
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]