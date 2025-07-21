FROM composer:2 as builder
WORKDIR /app
# Otimização: Copia só os arquivos de dependência primeiro
COPY composer.json composer.lock ./
# Instala as dependências
RUN composer install --no-dev --no-interaction --optimize-autoloader
# Agora sim, copia o resto do projeto
COPY . .

FROM php:8.3-cli
WORKDIR /app
# Copia o código e as dependências já instaladas do estágio anterior
COPY --from=builder /app .

RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# "Abre" a porta 8000 do contêiner para o mundo exterior
EXPOSE 8000

# O comando que será executado para iniciar o servidor
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]