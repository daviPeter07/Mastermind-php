<?php

namespace App\Bot;

use TelegramBot\Api\Types\Message;
use TelegramBot\Api\BotApi;
use App\Bot\Commands\AuthCommands;

class CommandHandler
{
    protected array $commandMap = [
        '/start' => [AuthCommands::class, 'start'],
    ];

    public function handle(Message $message, BotApi $telegram)
    {
        $text = $message->getText();
        $chatId = $message->getChat()->getId();

        // Verifica se a mensagem é um comando que está no nosso mapa
        if (isset($this->commandMap[$text])) {
            [$commandClass, $method] = $this->commandMap[$text];
            
            (new $commandClass())->$method($message, $telegram);
        } else {
            $telegram->sendMessage($chatId, 'Comando não reconhecido.');
        }
    }
}