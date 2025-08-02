<?php

namespace App\Controllers;

use App\Bot\CommandHandler;
use TelegramBot\Api\BotApi;

class WebhookController
{
    public function handle()
    {
        // Verifica se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method not allowed');
        }

        try {
            // Pega o JSON da requisição
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                http_response_code(400);
                exit('Invalid JSON');
            }
            
            // Inicializa o bot
            $botToken = getenv('TELEGRAM_BOT_TOKEN') ?: $_ENV['TELEGRAM_BOT_TOKEN'] ?? null;
            if (!$botToken) {
                throw new \Exception("TELEGRAM_BOT_TOKEN não definido");
            }
            
            $telegram = new BotApi($botToken);
            $commandHandler = new CommandHandler();
            
            // Processa a mensagem
            if (isset($data['message'])) {
                $commandHandler->handle($data['message'], $telegram);
            }
            
            // Responde com sucesso
            http_response_code(200);
            echo 'OK';
            
        } catch (\Exception $e) {
            error_log("Webhook error: " . $e->getMessage());
            http_response_code(500);
            echo 'Error';
        }
    }
} 