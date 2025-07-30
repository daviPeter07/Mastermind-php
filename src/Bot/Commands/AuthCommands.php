<?php

namespace App\Bot\Commands;

use TelegramBot\Api\Types\Message;
use TelegramBot\Api\BotApi;
use App\Services\UserService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Exception;

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
            $telegram->sendMessage($chatId, "Bem-vindo ao Mastermind! Use /login para entrar ou /register para criar uma conta.");
        }
    }

    public function login(Message $message, BotApi $telegram, array &$sessions)
    {
        $chatId = (string) $message->getChat()->getId();
        $sessions[$chatId] = ['state' => 'awaiting_login_email'];
        $telegram->sendMessage($chatId, 'Para fazer login, por favor, digite o email da sua conta Mastermind:');
    }

    public function register(Message $message, BotApi $telegram, array &$sessions)
    {
        $chatId = (string) $message->getChat()->getId();
        $sessions[$chatId] = ['state' => 'awaiting_register_name'];
        $telegram->sendMessage($chatId, 'Ótimo! Vamos criar sua conta. Primeiro, qual é o seu nome?');
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

    public function handleRegisterName(string $chatId, string $name, BotApi $telegram, array &$sessions)
    {
        $sessions[$chatId]['register_name'] = $name;
        $sessions[$chatId]['state'] = 'awaiting_register_email';
        $telegram->sendMessage($chatId, "Perfeito, {$name}. Agora, por favor, digite seu melhor email:");
    }

    public function handleRegisterEmail(string $chatId, string $email, BotApi $telegram, array &$sessions)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $telegram->sendMessage($chatId, "Hmm, este email não parece válido. Por favor, tente de novo.");
            return;
        }

        $sessions[$chatId]['register_email'] = $email;
        $sessions[$chatId]['state'] = 'awaiting_register_password';
        $telegram->sendMessage($chatId, "Email anotado. Por fim, crie uma senha (mínimo de 8 caracteres):");
    }

    public function handleRegisterPassword(string $chatId, string $password, BotApi $telegram, array &$sessions)
    {
        $name = $sessions[$chatId]['register_name'];
        $email = $sessions[$chatId]['register_email'];

        if (strlen($password) < 8) {
            $telegram->sendMessage($chatId, "Sua senha é muito curta. Ela precisa ter no mínimo 8 caracteres. Por favor, digite uma nova senha:");
            return;
        }

        try {
            $response = $this->httpClient->post('/api/auth/register', [
                'json' => ['name' => $name, 'email' => $email, 'password' => $password]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $token = $data['token'];
            $userFromApi = $data['user'];

            $this->userService->updateUser($userFromApi['id'], [
                'api_token' => $token,
                'telegram_chat_id' => $chatId,
                'bot_state' => 'authenticated'
            ]);

            $telegram->sendMessage($chatId, "✅ Conta criada e login realizado com sucesso! Bem-vindo ao Mastermind, {$userFromApi['name']}!");
        } catch (ClientException $e) {
            $errorBody = json_decode($e->getResponse()->getBody()->getContents(), true);
            $telegram->sendMessage($chatId, "❌ Erro: " . ($errorBody['error'] ?? 'Não foi possível criar sua conta.') . " Tente /register novamente.");
        } finally {
            unset($sessions[$chatId]);
        }
    }
}
