<?php

namespace App\Controllers;

use App\Bot\CommandHandler;
use TelegramBot\Api\BotApi;

class WebhookController
{
    public function handle()
    {
        error_log("Webhook recebido: " . $_SERVER['REQUEST_METHOD']);
        
        // Verifica se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("Webhook: Método não permitido - " . $_SERVER['REQUEST_METHOD']);
            http_response_code(405);
            exit('Method not allowed');
        }

        try {
            // Pega o JSON da requisição
            $input = file_get_contents('php://input');
            error_log("Webhook input: " . $input);
            
            if (empty($input)) {
                error_log("Webhook: Input vazio");
                http_response_code(400);
                exit('Empty input');
            }
            
            $data = json_decode($input, true);
            
            if (!$data) {
                error_log("Webhook: JSON inválido - " . json_last_error_msg());
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
                $chatId = $data['message']['chat']['id'];
                $text = $data['message']['text'] ?? 'sem texto';
                error_log("Webhook: Processando mensagem do chat {$chatId} - '{$text}'");
                
                try {
                    $commandHandler->handle($data['message'], $telegram);
                    error_log("Webhook: Mensagem processada com sucesso");
                } catch (\Exception $e) {
                    error_log("Webhook: Erro ao processar mensagem - " . $e->getMessage());
                    // Não falha o webhook, só loga o erro
                }
            } else {
                error_log("Webhook: Nenhuma mensagem encontrada no payload");
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