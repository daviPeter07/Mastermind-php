<?php

namespace App\Bot;

use TelegramBot\Api\Types\Message;
use TelegramBot\Api\BotApi;
use App\Services\UserService;
use App\Bot\Commands\AuthCommands;
use App\Bot\Commands\CategoryCommands;
use App\Bot\Commands\TaskCommands;
use App\Bot\Commands\HelpCommands;

class CommandHandler
{
    private UserService $userService;
    private array $sessions = [];

    private array $commandMap = [
        '/start'           => [AuthCommands::class, 'start'],
        '/login'           => [AuthCommands::class, 'login'],
        '/register'        => [AuthCommands::class, 'register'],
        '/logout'          => [AuthCommands::class, 'logout'],
        '/ajuda'           => [HelpCommands::class, 'show'],
        '/categorias'      => [CategoryCommands::class, 'list'],
        '/add_categoria'   => [CategoryCommands::class, 'create'],
        '/edit_categoria'  => [CategoryCommands::class, 'update'],
        '/del_categoria'   => [CategoryCommands::class, 'delete'],
        '/tarefas'         => [TaskCommands::class, 'list'],
        '/add_tarefa'      => [TaskCommands::class, 'create'],
        '/edit_tarefa'     => [TaskCommands::class, 'update'],
        '/del_tarefa'      => [TaskCommands::class, 'delete'],
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

        if ($state && str_starts_with($state, 'awaiting_login')) {
            $handler = new AuthCommands();
            if ($state === 'awaiting_login_email') {
                $handler->handleLoginEmail($chatId, $text, $telegram, $this->sessions);
                return;
            }
            if ($state === 'awaiting_login_password') {
                $handler->handleLoginPassword($chatId, $text, $telegram, $this->sessions);
                return;
            }
        }
        if ($state && str_starts_with($state, 'awaiting_register')) {
            error_log("DEBUG: Estado awaiting_register detectado: $state");
            $handler = new AuthCommands();
            if ($state === 'awaiting_register_name') {
                $handler->handleRegisterName($chatId, $text, $telegram, $this->sessions);
                return;
            }
            if ($state === 'awaiting_register_email') {
                $handler->handleRegisterEmail($chatId, $text, $telegram, $this->sessions);
                return;
            }
            if ($state === 'awaiting_register_password') {
                $handler->handleRegisterPassword($chatId, $text, $telegram, $this->sessions);
                return;
            }
        }

        if ($state && str_starts_with($state, 'awaiting_category')) {
            $handler = new CategoryCommands();
            if ($state === 'awaiting_category_name') {
                $handler->handleCategoryName($chatId, $text, $telegram, $this->sessions);
                return;
            }
            if ($state === 'awaiting_category_type') {
                $handler->handleCategoryType($chatId, $text, $telegram, $this->sessions);
                return;
            }
            if ($state === 'awaiting_category_id_for_edit') {
                $handler->handleCategoryIdForEdit($chatId, $text, $telegram, $this->sessions);
                return;
            }
            if ($state === 'awaiting_category_new_name') {
                $handler->handleCategoryNewName($chatId, $text, $telegram, $this->sessions);
                return;
            }
            if ($state === 'awaiting_category_id_for_delete') {
                $handler->handleCategoryIdForDelete($chatId, $text, $telegram, $this->sessions);
                return;
            }
            if ($state === 'awaiting_category_delete_confirmation') {
                $handler->handleCategoryDeleteConfirmation($chatId, $text, $telegram, $this->sessions);
                return;
            }
        }

        if ($state && str_starts_with($state, 'awaiting_task')) {
            $handler = new TaskCommands();
            if ($state === 'awaiting_task_content') {
                $handler->handleTaskContent($chatId, $text, $telegram, $this->sessions, $user);
                return;
            }
            if ($state === 'awaiting_task_category_id') {
                $handler->handleTaskCategoryId($chatId, $text, $telegram, $this->sessions);
                return;
            }
            if ($state === 'awaiting_task_due_date') {
                $handler->handleTaskDueDate($chatId, $text, $telegram, $this->sessions);
                return;
            }
            if ($state === 'awaiting_task_id_for_edit') {
                $handler->handleTaskIdForEdit($chatId, $text, $telegram, $this->sessions);
                return;
            }
            if ($state === 'awaiting_task_new_content') {
                $handler->handleTaskNewContent($chatId, $text, $telegram, $this->sessions);
                return;
            }
            if ($state === 'awaiting_task_id_for_delete') {
                $handler->handleTaskIdForDelete($chatId, $text, $telegram, $this->sessions);
                return;
            }
            if ($state === 'awaiting_task_delete_confirmation') {
                $handler->handleTaskDeleteConfirmation($chatId, $text, $telegram, $this->sessions);
                return;
            }
        }

        // Comando com parâmetro: /done <id>
        if (preg_match('/^\/done\s+(\d+)/', $text, $matches)) {
            if ($user && $user['api_token']) {
                (new TaskCommands())->done($message, $telegram, $user, $matches);
            } else {
                $telegram->sendMessage($chatId, 'Você precisa estar logado. Use /login.');
            }
            return;
        }

        // Comando com filtro: /tarefas <filtro>
        if (preg_match('/^\/tarefas\s+(.+)/', $text, $matches)) {
            if ($user && $user['api_token']) {
                $filter = strtolower(trim($matches[1]));
                (new TaskCommands())->list($message, $telegram, $user, $filter);
            } else {
                $telegram->sendMessage($chatId, 'Você precisa estar logado. Use /login.');
            }
            return;
        }

        if (isset($this->commandMap[$text])) {
            [$commandClass, $method] = $this->commandMap[$text];

            if (str_contains($text, 'add_') || str_contains($text, 'edit_') || str_contains($text, 'del_') || str_contains($text, 'login') || str_contains($text, 'register')) {
                (new $commandClass())->$method($message, $telegram, $this->sessions, $user);
            } else {
                // Validação para logout - requer autenticação
                if ($text === '/logout') {
                    if ($user && $user['api_token']) {
                        (new $commandClass())->$method($message, $telegram, $user);
                    } else {
                        $telegram->sendMessage($chatId, 'Você não está logado. Use /login para fazer login.');
                    }
                } else {
                    (new $commandClass())->$method($message, $telegram, $user);
                }
            }
        } else {
            $telegram->sendMessage($chatId, 'Comando não reconhecido. Use /start para começar.');
        }
    }
}
