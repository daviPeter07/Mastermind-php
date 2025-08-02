# Dockerfile (versão final para deploy)

# Estágio 1: Instala as dependências com o Composer
FROM composer:2 as builder
WORKDIR /app
# Otimização: Copia só os arquivos de dependência primeiro
COPY composer.json composer.lock ./
# Instala as dependências
RUN composer install --no-dev --no-interaction --optimize-autoloader
# Agora sim, copia o resto do projeto
COPY . .

# Estágio 2: Cria a imagem final e limpa do PHP
FROM php:8.3-cli
WORKDIR /app
# Copia o código e as dependências já instaladas do estágio anterior
COPY --from=builder /app .

# Instala as dependências de sistema: a do PostgreSQL e o Supervisor
RUN apt-get update && apt-get install -y \
    libpq-dev \
    supervisor \
    && docker-php-ext-install pdo pdo_pgsql

# Copia o arquivo de configuração do Supervisor para o local padrão
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copia o script de inicialização e o torna executável
COPY start.sh /app/start.sh
RUN chmod +x /app/start.sh

# "Abre" a porta 8000 do contêiner para o mundo exterior
EXPOSE 8000

# O comando que será executado para iniciar o servidor
CMD ["/app/start.sh"]
