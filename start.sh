#!/bin/bash

# Executa a migração do banco primeiro
echo "🔄 Executando migração do banco..."
php scripts/run-migration.php

# Inicia o Apache em background
apache2-foreground &

# Aguarda um pouco para o Apache inicializar
sleep 5

# Inicia o bot do Telegram
php scripts/run-bot.php 