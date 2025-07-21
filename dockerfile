FROM composer:2 as builder
WORKDIR /app
# Copia SÓ os arquivos de dependência primeiro
COPY composer.json composer.lock ./
# Instala as dependências 
RUN composer install --no-dev --no-interaction --optimize-autoloader
COPY . .

# Estágio 2: Imagem Final com PHP
FROM php:8.3-cli
WORKDIR /app
# Copia o código e as dependências já instaladas do estágio anterior
COPY --from=builder /app .

# Instala a extensão para o PHP poder conversar com o PostgreSQL
RUN docker-php-ext-install pdo pdo_pgsql

# "Abre" a porta 8000 do contêiner para o mundo exterior
EXPOSE 8000

# O comando que será executado para iniciar o servidor
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]