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
        '/add_categoria'   => [CategoryCommands::class, 'create'],
        '/edit_categoria'  => [CategoryCommands::class, 'update'],
        '/del_categoria'   => [CategoryCommands::class, 'delete'],
    ];

    public function __construct()
    {
        $this->userService = new UserService();
    }

    public function handle(Message $message, BotApi $telegram)
    {
        $text = $message->getText();
        $chatId = (string) $message->getChat()->getId();
        $user = $this->userService->findByTelegramChatId($chatId);

        if ($user && $user['api_token']) {
            $this->sessions[$chatId]['api_token'] = $user['api_token'];
        }

        $state = $this->sessions[$chatId]['state'] ?? null;

        $authHandler = new AuthCommands();
        if ($state === 'awaiting_login_email') {
            $authHandler->handleLoginEmail($chatId, $text, $telegram, $this->sessions);
            return;
        }
        if ($state === 'awaiting_login_password') {
            $authHandler->handleLoginPassword($chatId, $text, $telegram, $this->sessions);
            return;
        }

        $categoryHandler = new CategoryCommands();
        if ($state === 'awaiting_category_name') {
            $categoryHandler->handleCategoryName($chatId, $text, $telegram, $this->sessions);
            return;
        }
        if ($state === 'awaiting_category_type') {
            $categoryHandler->handleCategoryType($chatId, $text, $telegram, $this->sessions);
            return;
        }
        if ($state === 'awaiting_category_id_for_edit') {
            $categoryHandler->handleCategoryIdForEdit($chatId, $text, $telegram, $this->sessions);
            return;
        }
        if ($state === 'awaiting_category_new_name') {
            $categoryHandler->handleCategoryNewName($chatId, $text, $telegram, $this->sessions);
            return;
        }
        if ($state === 'awaiting_category_id_for_delete') {
            $categoryHandler->handleCategoryIdForDelete($chatId, $text, $telegram, $this->sessions);
            return;
        }
        if ($state === 'awaiting_category_delete_confirmation') {
            $categoryHandler->handleCategoryDeleteConfirmation($chatId, $text, $telegram, $this->sessions);
            return;
        }

        if (isset($this->commandMap[$text])) {
            [$commandClass, $method] = $this->commandMap[$text];

            if (in_array($text, ['/login', '/add_categoria', '/edit_categoria', '/del_categoria'])) {
                (new $commandClass())->$method($message, $telegram, $this->sessions, $user);
            } else {
                (new $commandClass())->$method($message, $telegram, $user);
            }
        } else {
            $telegram->sendMessage($chatId, 'Comando não reconhecido. Use /start para começar.');
        }
    }
}
