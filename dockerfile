# Dockerfile (versão final para deploy)

# Estágio 1: Instala as dependências com o Composer
FROM composer:2 as builder
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --optimize-autoloader
COPY . .

# Estágio 2: Cria a imagem final e limpa do PHP
FROM php:8.3-cli
WORKDIR /app
COPY --from=builder /app .

# Instala as dependências de sistema: a do PostgreSQL e o Supervisor
RUN apt-get update && apt-get install -y \
    libpq-dev \
    supervisor \
    && docker-php-ext-install pdo pdo_pgsql

# Copia nosso arquivo de configuração do Supervisor para o lugar certo
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Expõe a porta 8000 para a API
EXPOSE 8000

# Comando final: inicia o Supervisor, que vai gerenciar nossos 2 processos
CMD ["/app/start.sh"]