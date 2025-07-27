<?php

namespace App\Bot;

use TelegramBot\Api\Types\Message;
use TelegramBot\Api\BotApi;
use App\Services\UserService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class CommandHandler
{
    private UserService $userService;
    private Client $httpClient;
    private array $sessions = [];

    public function __construct()
    {
        $this->userService = new UserService();
        $this->httpClient = new Client(['base_uri' => 'http://app:8000']);
    }

    public function handle(Message $message, BotApi $telegram)
    {
        $text = $message->getText();
        $chatId = (string) $message->getChat()->getId();

        $state = $this->sessions[$chatId]['state'] ?? null;

        if ($state === 'awaiting_login_email') {
            $this->handleLoginEmail($chatId, $text, $telegram);
            return;
        }

        if ($state === 'awaiting_login_password') {
            $this->handleLoginPassword($chatId, $text, $telegram);
            return;
        }

        $user = $this->userService->findByTelegramChatId($chatId);

        switch ($text) {
            case '/start':
                if ($user && $user['api_token']) {
                    $telegram->sendMessage($chatId, "Bem-vindo de volta, {$user['name']}! Você já está logado.");
                } else {
                    $telegram->sendMessage($chatId, "Bem-vindo ao Mastermind! Use /login para entrar.");
                }
                break;

            case '/login':
                $this->sessions[$chatId] = ['state' => 'awaiting_login_email'];
                $telegram->sendMessage($chatId, 'Para fazer login, por favor, digite o email da sua conta Mastermind:');
                break;

            case '/categorias':
                if ($user && $user['api_token']) {
                    $this->listCategories($chatId, $user['api_token'], $telegram);
                } else {
                    $telegram->sendMessage($chatId, 'Você precisa estar logado para ver suas categorias. Use /login.');
                }
                break;

            default:
                $telegram->sendMessage($chatId, 'Comando não reconhecido. Use /start para começar.');
                break;
        }
    }

    private function handleLoginEmail(string $chatId, string $email, BotApi $telegram)
    {
        $user = $this->userService->findByEmail($email);
        if (!$user) {
            $telegram->sendMessage($chatId, "Email não encontrado. Tente novamente com outro email ou use /register.");
            unset($this->sessions[$chatId]);
            return;
        }

        $this->sessions[$chatId]['state'] = 'awaiting_login_password';
        $this->sessions[$chatId]['email'] = $email;
        $telegram->sendMessage($chatId, "Email recebido. Agora, digite sua senha:");
    }

    private function handleLoginPassword(string $chatId, string $password, BotApi $telegram)
    {
        $email = $this->sessions[$chatId]['email'];

        try {
            $response = $this->httpClient->post('/api/auth/login', [
                'json' => ['email' => $email, 'password' => $password]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $token = $data['token'];
            $userFromApi = $data['user'];
            $this->userService->updateUser($userFromApi['id'], [
                'api_token' => $token,
                'telegram_chat_id' => $chatId,
                'bot_state' => 'authenticated'
            ]);

            $telegram->sendMessage($chatId, "✅ Login realizado com sucesso! Bem-vindo de volta, {$userFromApi['name']}.");
        } catch (ClientException $e) {
            $telegram->sendMessage($chatId, "❌ Email ou senha inválidos. Use /login para tentar novamente.");
        } finally {
            unset($this->sessions[$chatId]);
        }
    }

    private function listCategories(string $chatId, string $apiToken, BotApi $telegram)
    {
        try {
            $response = $this->httpClient->get('/api/categories', [
                'headers' => ['Authorization' => 'Bearer ' . $apiToken]
            ]);
            $categories = json_decode($response->getBody()->getContents(), true);

            if (empty($categories)) {
                $reply = 'Você ainda não tem nenhuma categoria cadastrada.';
            } else {
                $reply = "Suas categorias:\n";
                foreach ($categories as $category) {
                    $reply .= "- {$category['name']} (ID: {$category['id']})\n";
                }
            }
            $telegram->sendMessage($chatId, $reply);
        } catch (ClientException $e) {
            $telegram->sendMessage($chatId, 'Ocorreu um erro ao buscar suas categorias.');
        }
    }
}
