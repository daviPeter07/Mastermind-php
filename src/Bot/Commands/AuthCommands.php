<?php

namespace App\Bot\Commands;

use TelegramBot\Api\Types\Message;
use TelegramBot\Api\BotApi;
use App\Services\UserService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class AuthCommands
{
    private UserService $userService;
    private Client $httpClient;

    public function __construct()
    {
        $this->userService = new UserService();
        $this->httpClient = new Client(['base_uri' => 'http://app:8000']);
    }

    public function start(Message $message, BotApi $telegram, ?array $user)
    {
        $chatId = (string) $message->getChat()->getId();
        if ($user && $user['api_token']) {
            $telegram->sendMessage($chatId, "Bem-vindo de volta, {$user['name']}! Você já está logado.");
        } else {
            $telegram->sendMessage($chatId, "Bem-vindo ao Mastermind! Use /login para entrar.");
        }
    }

    public function login(Message $message, BotApi $telegram, array &$sessions)
    {
        $chatId = (string) $message->getChat()->getId();
        $sessions[$chatId] = ['state' => 'awaiting_login_email'];
        $telegram->sendMessage($chatId, 'Para fazer login, por favor, digite o email da sua conta Mastermind:');
    }

    public function handleLoginEmail(string $chatId, string $email, BotApi $telegram, array &$sessions)
    {
        $user = $this->userService->findByEmail($email);
        if (!$user) {
            $telegram->sendMessage($chatId, "Email não encontrado. Tente novamente ou use /register.");
            unset($sessions[$chatId]);
            return;
        }

        $sessions[$chatId]['state'] = 'awaiting_login_password';
        $sessions[$chatId]['email'] = $email;
        $telegram->sendMessage($chatId, "Email recebido. Agora, digite sua senha:");
    }

    public function handleLoginPassword(string $chatId, string $password, BotApi $telegram, array &$sessions)
    {
        $email = $sessions[$chatId]['email'];

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
            unset($sessions[$chatId]);
        }
    }
}
