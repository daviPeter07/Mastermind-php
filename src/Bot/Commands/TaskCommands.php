<?php

namespace App\Bot\Commands;

use TelegramBot\Api\Types\Message;
use TelegramBot\Api\BotApi;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use App\Services\UserService;

class TaskCommands
{
  private Client $httpClient;
  private UserService $userService;

  public function __construct()
  {
    $this->httpClient = new Client(['base_uri' => 'http://app:8000']);
    $this->userService = new UserService();
  }

  public function list(Message $message, BotApi $telegram, ?array $user)
  {
    $chatId = $message->getChat()->getId();
    $apiToken = $user['api_token'];

    try {
      $response = $this->httpClient->get('/api/tasks', [
        'headers' => ['Authorization' => 'Bearer ' . $apiToken]
      ]);
      $tasks = json_decode($response->getBody()->getContents(), true);

      if (empty($tasks)) {
        $reply = 'Você ainda não tem nenhuma tarefa. Use /add_tarefa para criar uma!';
      } else {
        $reply = "Suas tarefas:\n\n";
        foreach ($tasks as $task) {
          $statusEmoji = $task['status'] === 'CONCLUIDA' ? '✅' : '📝';
          $reply .= "{$statusEmoji} *{$task['content']}*\n";
          $reply .= "   - ID: `{$task['id']}`\n";
          $reply .= "   - Categoria: {$task['category_name']}\n\n";
        }
      }
      $telegram->sendMessage($chatId, $reply, 'Markdown');
    } catch (ClientException $e) {
      $telegram->sendMessage($chatId, 'Ocorreu um erro ao buscar suas tarefas.');
    }
  }

  public function create(Message $message, BotApi $telegram, array &$sessions)
  {
    $chatId = (string) $message->getChat()->getId();
    $sessions[$chatId] = ['state' => 'awaiting_task_content'];
    $telegram->sendMessage($chatId, 'Qual é a nova tarefa?');
  }

  public function update(Message $message, BotApi $telegram, array &$sessions, ?array $user)
  {
    $chatId = (string) $message->getChat()->getId();
    $this->list($message, $telegram, $user); // Lista as tarefas para o usuário
    $sessions[$chatId] = ['state' => 'awaiting_task_id_for_edit'];
    $telegram->sendMessage($chatId, "\nQual o ID da tarefa que você quer editar?");
  }

  public function delete(Message $message, BotApi $telegram, array &$sessions, ?array $user)
  {
    $chatId = (string) $message->getChat()->getId();
    $this->list($message, $telegram, $user); // Lista as tarefas
    $sessions[$chatId] = ['state' => 'awaiting_task_id_for_delete'];
    $telegram->sendMessage($chatId, "\nQual o ID da tarefa que você quer deletar?");
  }

  public function done(Message $message, BotApi $telegram, ?array $user, array $matches)
  {
    $chatId = (string) $message->getChat()->getId();
    $taskId = (int) $matches[1]; // O ID é capturado pelo regex no CommandHandler
    $apiToken = $user['api_token'];

    try {
      $this->httpClient->patch("/api/tasks/{$taskId}/status", [
        'headers' => ['Authorization' => 'Bearer ' . $apiToken],
        'json' => ['status' => 'CONCLUIDA']
      ]);
      $telegram->sendMessage($chatId, "✅ Tarefa de ID `{$taskId}` marcada como concluída!", 'Markdown');
    } catch (ClientException $e) {
      $telegram->sendMessage($chatId, "❌ Erro ao atualizar tarefa. Verifique se o ID `{$taskId}` é válido e pertence a você.", 'Markdown');
    }
  }

  public function handleTaskContent(string $chatId, string $content, BotApi $telegram, array &$sessions, ?array $user)
  {
    $sessions[$chatId]['task_content'] = $content;
    $apiToken = $user['api_token'];

    try {
      $response = $this->httpClient->get('/api/categories', [
        'headers' => ['Authorization' => 'Bearer ' . $apiToken]
      ]);
      $categories = json_decode($response->getBody()->getContents(), true);

      if (empty($categories)) {
        $telegram->sendMessage($chatId, "Você precisa ter pelo menos uma categoria. Use /add_categoria primeiro.");
        unset($sessions[$chatId]);
        return;
      }

      $reply = "Entendido. A tarefa é '{$content}'.\n\nEm qual categoria ela se encaixa? Digite o ID:\n";
      foreach ($categories as $category) {
        $reply .= "- {$category['name']} (ID: {$category['id']})\n";
      }

      $sessions[$chatId]['state'] = 'awaiting_task_category_id';
      $telegram->sendMessage($chatId, $reply);
    } catch (ClientException $e) {
      $telegram->sendMessage($chatId, 'Ocorreu um erro ao buscar suas categorias.');
      unset($sessions[$chatId]);
    }
  }

  public function handleTaskCategoryId(string $chatId, string $categoryId, BotApi $telegram, array &$sessions)
  {
    if (!is_numeric($categoryId)) {
      $telegram->sendMessage($chatId, "ID inválido. Por favor, digite apenas o número.");
      return;
    }

    $sessions[$chatId]['category_id'] = (int)$categoryId;
    $sessions[$chatId]['state'] = 'awaiting_task_due_date';
    $telegram->sendMessage($chatId, "Ok. A tarefa tem uma data de entrega? (ex: `AAAA-MM-DD HH:MM:SS`) ou digite `nao`.");
  }

  public function handleTaskDueDate(string $chatId, string $dueDate, BotApi $telegram, array &$sessions)
  {
    $content = $sessions[$chatId]['task_content'];
    $categoryId = $sessions[$chatId]['category_id'];
    $apiToken = $sessions[$chatId]['api_token'];
    $data = ['content' => $content, 'category_id' => $categoryId];

    if (strtolower($dueDate) !== 'nao' && strtolower($dueDate) !== 'não') {
      $data['due_date'] = $dueDate;
    }

    try {
      $this->httpClient->post('/api/tasks', [
        'headers' => ['Authorization' => 'Bearer ' . $apiToken],
        'json' => $data
      ]);
      $telegram->sendMessage($chatId, "✅ Tarefa '{$content}' criada com sucesso!");
    } catch (ClientException $e) {
      $errorBody = json_decode($e->getResponse()->getBody()->getContents(), true);
      $telegram->sendMessage($chatId, '❌ Erro: ' . ($errorBody['error'] ?? 'Não foi possível criar a tarefa.'));
    } finally {
      unset($sessions[$chatId]);
    }
  }

  public function handleTaskIdForEdit(string $chatId, string $id, BotApi $telegram, array &$sessions)
  {
    $sessions[$chatId]['task_id_for_edit'] = (int)$id;
    $sessions[$chatId]['state'] = 'awaiting_task_new_content';
    $telegram->sendMessage($chatId, "Ok, qual a nova descrição para a tarefa de ID {$id}?");
  }

  public function handleTaskNewContent(string $chatId, string $newContent, BotApi $telegram, array &$sessions)
  {
    $taskId = $sessions[$chatId]['task_id_for_edit'];
    $apiToken = $sessions[$chatId]['api_token'];

    try {
      $this->httpClient->put("/api/tasks/{$taskId}", [
        'headers' => ['Authorization' => 'Bearer ' . $apiToken],
        'json' => ['content' => $newContent]
      ]);
      $telegram->sendMessage($chatId, "✅ Tarefa atualizada com sucesso!");
    } catch (ClientException $e) {
      $telegram->sendMessage($chatId, "❌ Erro ao atualizar a tarefa. Verifique o ID.");
    } finally {
      unset($sessions[$chatId]);
    }
  }

  public function handleTaskIdForDelete(string $chatId, string $id, BotApi $telegram, array &$sessions)
  {
    $sessions[$chatId]['task_id_for_delete'] = (int)$id;
    $sessions[$chatId]['state'] = 'awaiting_task_delete_confirmation';
    $telegram->sendMessage($chatId, "⚠️ Tem certeza que quer deletar a tarefa de ID {$id}?\n\nDigite `sim` para confirmar.");
  }

  public function handleTaskDeleteConfirmation(string $chatId, string $confirmation, BotApi $telegram, array &$sessions)
  {
    if (strtolower($confirmation) !== 'sim') {
      $telegram->sendMessage($chatId, "Deleção cancelada.");
      unset($sessions[$chatId]);
      return;
    }

    $taskId = $sessions[$chatId]['task_id_for_delete'];
    $apiToken = $sessions[$chatId]['api_token'];

    try {
      $this->httpClient->delete("/api/tasks/{$taskId}", [
        'headers' => ['Authorization' => 'Bearer ' . $apiToken]
      ]);
      $telegram->sendMessage($chatId, "🗑️ Tarefa deletada com sucesso!");
    } catch (ClientException $e) {
      $telegram->sendMessage($chatId, "❌ Erro ao deletar a tarefa. Verifique o ID.");
    } finally {
      unset($sessions[$chatId]);
    }
  }
}
