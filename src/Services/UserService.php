<?php

namespace App\Services;

use App\Core\Database;
use PDO;

class UserService
{

  private PDO $db;
  public function __construct()
  {
    $this->db = Database::getConnection();
  }


  public function getUsers(): array
  {
    $stmt = $this->db->prepare("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getUserById(string $id): ?array
  {
    $stmt = $this->db->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
    $stmt->execute([$id]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user ?: null;
  }


  public function updateUser(string $id, array $data): ?array
  {
    $fields = [];
    foreach (array_keys($data) as $field) {
      $fields[] = "$field = ?";
    }
    $setClause = implode(', ', $fields);

    $stmt = $this->db->prepare("UPDATE users SET $setClause WHERE id = ?");

    $values = array_values($data);
    $values[] = $id;
    $stmt->execute($values);

    return $this->getUserById($id);
  }


  public function deleteUser(string $id): bool
  {
    $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
    return $stmt->execute([$id]);
  }

  public function findByTelegramChatId(string $chatId): ?array
  {
    $stmt = $this->db->prepare("SELECT * FROM users WHERE telegram_chat_id = ?");
    $stmt->execute([$chatId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
  }

  public function linkTelegramAccount(string $email, string $chatId): bool
  {
    //verifica se o email existe
    $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
      return false;
    }

    //se o email existe, atualiza o registro com o chatId e o estado do bot
    $stmt = $this->db->prepare("UPDATE users SET telegram_chat_id = ?, bot_state = 'awaiting_password' WHERE email = ?");
    return $stmt->execute([$chatId, $email]);
  }

  public function findByEmail(string $email): ?array
  {
    $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
  }
}
