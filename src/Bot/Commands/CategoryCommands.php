<?php

namespace App\Bot\Commands;

use TelegramBot\Api\Types\Message;
use TelegramBot\Api\BotApi;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class CategoryCommands
{
  private Client $httpClient;

  public function __construct()
  {
    $this->httpClient = new Client(['base_uri' => 'http://app:8000']);
  }

  public function list(Message $message, BotApi $telegram, ?array $user)
  {
    $chatId = $message->getChat()->getId();
    if (!$user || !$user['api_token']) {
      $telegram->sendMessage($chatId, 'Você precisa estar logado para ver suas categorias. Use /login.');
      return;
    }

    try {
      $response = $this->httpClient->get('/api/categories', [
        'headers' => ['Authorization' => 'Bearer ' . $user['api_token']]
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
