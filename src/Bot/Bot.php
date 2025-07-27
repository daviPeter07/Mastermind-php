<?php

namespace App\Bot;

use TelegramBot\Api\BotApi;
use Exception;

class Bot
{
  private BotApi $telegram;

  public function __construct()
  {
    $botToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? null;

    if (!$botToken) {
      throw new Exception("TELEGRAM_BOT_TOKEN não definido no .env");
    }

    $this->telegram = new BotApi($botToken);

  }

  public function listen()
  {
    echo "Bot Mastermind running...\n";

    $offset = 0;

    // O loop infinito que busca mensagens
    while (true) {
      $updates = $this->telegram->getUpdates($offset, 100, 30);

      foreach ($updates as $update) {
        $offset = $update->getUpdateId() + 1;
        $message = $update->getMessage();

        if ($message) {
          echo "Mensagem recebida de: " . $message->getChat()->getUsername() . "\n";
          echo "Texto: " . $message->getText() . "\n\n";

          // TODO: Futuramente, em vez de 'echo', vamos chamar o CommandHandler aqui:
          // $this->commandHandler->handle($message, $this->telegram);
        }
      }

      sleep(1);
    }
  }
}
