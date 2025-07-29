#!/bin/bash

echo "🚀 Iniciando o Bot Mastermind..."

# Verificar se as variáveis de ambiente estão configuradas
if [ -z "$TELEGRAM_BOT_TOKEN" ]; then
    echo "❌ ERRO: TELEGRAM_BOT_TOKEN não está configurado!"
    echo "Por favor, configure a variável TELEGRAM_BOT_TOKEN no arquivo .env"
    exit 1
fi

if [ -z "$JWT_SECRET" ]; then
    echo "❌ ERRO: JWT_SECRET não está configurado!"
    echo "Por favor, configure a variável JWT_SECRET no arquivo .env"
    exit 1
fi

echo "✅ Variáveis de ambiente verificadas"

# Iniciar o bot
echo "🤖 Iniciando o bot..."
php scripts/run-bot.php 