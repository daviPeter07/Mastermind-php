FROM composer:2 as builder
WORKDIR /app
# Otimização: Copia só os arquivos de dependência primeiro
COPY composer.json composer.lock ./
# Instala as dependências
RUN composer install --no-dev --no-interaction --optimize-autoloader
# Agora sim, copia o resto do projeto
COPY . .

FROM php:8.3-apache
WORKDIR /app
# Copia o código e as dependências já instaladas do estágio anterior
COPY --from=builder /app .

# Instala as extensões necessárias
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && a2enmod rewrite \
    && a2enmod headers

# Configura o Apache para servir a partir do diretório public
RUN sed -i 's/DocumentRoot \/var\/www\/html/DocumentRoot \/app\/public/' /etc/apache2/sites-available/000-default.conf
RUN sed -i 's/<Directory \/var\/www\/>/<Directory \/app\/public\/>/' /etc/apache2/sites-available/000-default.conf

# "Abre" a porta 80 do contêiner para o mundo exterior
EXPOSE 80

# O comando que será executado para iniciar o servidor Apache
CMD ["apache2-foreground"]