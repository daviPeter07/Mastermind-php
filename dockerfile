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

# Copia a configuração Apache personalizada
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Configura permissões e propriedade
RUN chown -R www-data:www-data /app && \
    chmod -R 755 /app

# Torna o script executável
COPY start.sh /app/start.sh
RUN chmod +x /app/start.sh

# "Abre" a porta 80 do contêiner para o mundo exterior
EXPOSE 80

# O comando que será executado para iniciar Apache + Bot
CMD ["/app/start.sh"]