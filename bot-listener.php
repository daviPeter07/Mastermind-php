<?php

require __DIR__ . '/vendor/autoload.php';
use TelegramBot\Api\BotApi;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$botToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? null;
$apiToken = $_ENV['MASTERMIND_API_TOKEN'] ?? null;
$apiUrl = 'http://app:8000/api';

if (!$botToken || !$apiToken) {
    die("Erro: Por favor, defina TELEGRAM_BOT_TOKEN e MASTERMIND_API_TOKEN no seu arquivo .env\n");
}

try {
    $bot = new BotApi($botToken);
    $httpClient = new \GuzzleHttp\Client();

    echo "🤖 Bot Mastermind ouvindo no Telegram...\n";

    // Loop infinito para buscar novas mensagens
    $offset = 0;
    while (true) {
        // Pede ao Telegram as últimas mensagens não lidas
        $updates = $bot->getUpdates($offset, 100, 30);

        foreach ($updates as $update) {
            $offset = $update->getUpdateId() + 1;
            $message = $update->getMessage();
            $chatId = $message->getChat()->getId();
            $text = $message->getText();

            //Comandos do bot
            if ($text === '/start') {
                $bot->sendMessage(
                    $chatId,
                    'Olá, Mestre! Mastermind no ar. Use /categorias para ver suas categorias.'
                );
            } elseif ($text === '/categorias') {
                try {
                    $response = $httpClient->request('GET', $apiUrl . '/categories', [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $apiToken,
                            'Accept'        => 'application/json',
                        ]
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
                    $bot->sendMessage($chatId, $reply);
                } catch (\GuzzleHttp\Exception\ClientException $e) {
                    $errorBody = json_decode($e->getResponse()->getBody()->getContents(), true);
                    $bot->sendMessage($chatId, 'Erro ao buscar categorias: ' . ($errorBody['error'] ?? 'Não autorizado.'));
                }
            }
        }
        sleep(1);
    }
} catch (\Exception $e) {
    die("Bot falhou: " . $e->getMessage() . "\n");
}
