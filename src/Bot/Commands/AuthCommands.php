<?php

namespace App\Bot\Commands;

use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Message;

class AuthCommands
{
    public function start(Message $message, BotApi $telegram)
    {
        $chatId = $message->getChat()->getId();
        $text = 'Olá, Mestre! Mastermind no ar. Para começar, use /login ou /register.';
        
        $telegram->sendMessage($chatId, $text);
    }
}