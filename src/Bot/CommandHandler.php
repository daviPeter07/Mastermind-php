<?php

namespace App\Bot;

use TelegramBot\Api\Types\Message;
use TelegramBot\Api\BotApi;
use App\Services\UserService;
use App\Bot\Commands\AuthCommands;
use App\Bot\Commands\CategoryCommands;

class CommandHandler
{
    private UserService $userService;
    private array $sessions = [];

    private array $commandMap = [
        '/start'      => [AuthCommands::class, 'start'],
        '/login'      => [AuthCommands::class, 'login'],
        '/categorias' => [CategoryCommands::class, 'list'],
    ];

    public function __construct()
    {
        $this->userService = new UserService();
    }

    public function handle(Message $message, BotApi $telegram)
    {
        $text = $message->getText();
        $chatId = (string) $message->getChat()->getId();

        $state = $this->sessions[$chatId]['state'] ?? null;

        if ($state === 'awaiting_login_email') {
            (new AuthCommands())->handleLoginEmail($chatId, $text, $telegram, $this->sessions);
            return;
        }
        if ($state === 'awaiting_login_password') {
            (new AuthCommands())->handleLoginPassword($chatId, $text, $telegram, $this->sessions);
            return;
        }

        if (isset($this->commandMap[$text])) {
            $user = $this->userService->findByTelegramChatId($chatId);
            [$commandClass, $method] = $this->commandMap[$text];

            if ($text === '/login') {
                (new $commandClass())->$method($message, $telegram, $this->sessions);
            } else {
                (new $commandClass())->$method($message, $telegram, $user);
            }
        } else {
            $telegram->sendMessage($chatId, 'Comando não reconhecido. Use /start para começar.');
        }
    }
}
