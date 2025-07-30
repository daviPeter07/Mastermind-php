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

  public function create(Message $message, BotApi $telegram, array &$sessions)
  {
    $chatId = (string) $message->getChat()->getId();
    $sessions[$chatId] = ['state' => 'awaiting_category_name'];
    $telegram->sendMessage($chatId, 'Qual o nome da nova categoria?');
  }

  public function update(Message $message, BotApi $telegram, array &$sessions, ?array $user)
  {
    $chatId = (string) $message->getChat()->getId();
    // Primeiro, lista as categorias para o usuário saber qual editar
    $this->list($message, $telegram, $user);
    $sessions[$chatId] = ['state' => 'awaiting_category_id_for_edit'];
    $telegram->sendMessage($chatId, "\nQual o ID da categoria que você quer editar?");
  }

  public function delete(Message $message, BotApi $telegram, array &$sessions, ?array $user)
  {
    $chatId = (string) $message->getChat()->getId();
    // Lista as categorias para o usuário saber qual deletar
    $this->list($message, $telegram, $user);
    $sessions[$chatId] = ['state' => 'awaiting_category_id_for_delete'];
    $telegram->sendMessage($chatId, "\nQual o ID da categoria que você quer deletar?");
  }

  public function handleCategoryName(string $chatId, string $name, BotApi $telegram, array &$sessions)
  {
    $sessions[$chatId]['state'] = 'awaiting_category_type';
    $sessions[$chatId]['category_name'] = $name;
    $telegram->sendMessage($chatId, "Entendido. O nome será '{$name}'.\n\nQual o tipo? Digite `TASK` ou `FINANCE`.");
  }

  public function handleCategoryType(string $chatId, string $type, BotApi $telegram, array &$sessions)
  {
    $name = $sessions[$chatId]['category_name'];
    $apiToken = $sessions[$chatId]['api_token'];

    if (!in_array(strtoupper($type), ['TASK', 'FINANCE'])) {
      $telegram->sendMessage($chatId, "Tipo inválido. Por favor, digite `TASK` ou `FINANCE`.");
      return;
    }

    try {
      $this->httpClient->post('/api/categories', [
        'headers' => ['Authorization' => 'Bearer ' . $apiToken],
        'json' => ['name' => $name, 'type' => strtoupper($type)]
      ]);
      $telegram->sendMessage($chatId, "✅ Categoria '{$name}' criada com sucesso!");
    } catch (ClientException $e) {
      $errorBody = json_decode($e->getResponse()->getBody()->getContents(), true);
      $telegram->sendMessage($chatId, '❌ Erro: ' . ($errorBody['error'] ?? 'Não foi possível criar a categoria.'));
    } finally {
      unset($sessions[$chatId]);
    }
  }

  public function handleCategoryIdForEdit(string $chatId, string $id, BotApi $telegram, array &$sessions)
  {
    $sessions[$chatId]['state'] = 'awaiting_category_new_name';
    $sessions[$chatId]['category_id'] = $id;
    $telegram->sendMessage($chatId, "Ok. Qual será o novo nome para a categoria de ID {$id}?");
  }

  public function handleCategoryNewName(string $chatId, string $newName, BotApi $telegram, array &$sessions)
  {
    $id = $sessions[$chatId]['category_id'];
    $apiToken = $sessions[$chatId]['api_token'];

    try {
      $this->httpClient->put("/api/categories/{$id}", [
        'headers' => ['Authorization' => 'Bearer ' . $apiToken],
        'json' => ['name' => $newName]
      ]);
      $telegram->sendMessage($chatId, "✅ Categoria atualizada com sucesso!");
    } catch (ClientException $e) {
      $telegram->sendMessage($chatId, '❌ Erro: Não foi possível atualizar a categoria. Verifique o ID.');
    } finally {
      unset($sessions[$chatId]);
    }
  }

  public function handleCategoryIdForDelete(string $chatId, string $id, BotApi $telegram, array &$sessions)
  {
    $sessions[$chatId]['state'] = 'awaiting_category_delete_confirmation';
    $sessions[$chatId]['category_id'] = $id;
    $telegram->sendMessage($chatId, "⚠️ Tem certeza que quer deletar a categoria de ID {$id}? Essa ação não pode ser desfeita.\n\nDigite `sim` para confirmar.");
  }

  public function handleCategoryDeleteConfirmation(string $chatId, string $confirmation, BotApi $telegram, array &$sessions)
  {
    if (strtolower($confirmation) !== 'sim') {
      $telegram->sendMessage($chatId, "Deleção cancelada.");
      unset($sessions[$chatId]);
      return;
    }

    $id = $sessions[$chatId]['category_id'];
    $apiToken = $sessions[$chatId]['api_token'];

    try {
      $this->httpClient->delete("/api/categories/{$id}", [
        'headers' => ['Authorization' => 'Bearer ' . $apiToken]
      ]);
      $telegram->sendMessage($chatId, "🗑️ Categoria deletada com sucesso!");
    } catch (ClientException $e) {
      $telegram->sendMessage($chatId, '❌ Erro: Não foi possível deletar a categoria. Verifique o ID.');
    } finally {
      unset($sessions[$chatId]);
    }
  }
}
