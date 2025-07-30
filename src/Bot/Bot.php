<?php

namespace App\Bot;

use TelegramBot\Api\BotApi;
use Exception;
use TelegramBot\Api\HttpException;

class Bot
{
  private BotApi $telegram;
  private CommandHandler $commandHandler;

  public function __construct()
  {
    $botToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? null;
    if (!$botToken) {
      throw new Exception("TELEGRAM_BOT_TOKEN não definido no .env");
    }

    $this->telegram = new BotApi($botToken);
    $this->telegram->setCurlOption(CURLOPT_TIMEOUT, 60);

    $this->commandHandler = new CommandHandler();
  }

  public function listen()
  {
    echo "🤖 Bot Mastermind (Estruturado) iniciando...\n";

    // 1. Valida o token antes de iniciar o loop principal
    if (!$this->validateBotToken()) {
    }

    $offset = 0;
    $errorCounter = 0;

    while (true) {
      try {
        $updates = $this->telegram->getUpdates($offset, 100, 30);

        $errorCounter = 0;

        foreach ($updates as $update) {
          $offset = $update->getUpdateId() + 1;
          $message = $update->getMessage();

          if ($message) {
            try {
              $this->commandHandler->handle($message, $this->telegram);
            } catch (Exception $e) {
              echo "❗️ Erro ao processar mensagem: " . $e->getMessage() . "\n";
            }
          }
        }

        sleep(1);
      } catch (Exception $e) {
        $errorCounter++;
        $this->handleListenError($e, $errorCounter);
      }
    }
  }

  /**
   * Valida o token do bot ao se conectar com a API do Telegram.
   */
  private function validateBotToken(): bool
  {
    try {
      $botInfo = $this->telegram->getMe();
      echo "✅ Bot conectado como @{$botInfo->getUsername()}\n";
      return true;
    } catch (Exception $e) {
      echo "❌ Erro fatal ao validar token: " . $e->getMessage() . "\n";
      return false;
    }
  }

  /**
   * Centraliza a lógica de tratamento de erros do loop principal.
   */
  private function handleListenError(Exception $e, int &$errorCounter)
  {
    $maxErrors = 5;
    $retryDelay = 5;
    $longDelay = 30;

    echo "❌ Erro no loop principal (tentativa {$errorCounter}/{$maxErrors}): {$e->getMessage()}\n";

    if ($errorCounter >= $maxErrors) {
      echo "Muitos erros consecutivos. Pausando por {$longDelay} segundos...\n";
      sleep($longDelay);
      $errorCounter = 0;
    } else {
      sleep($retryDelay);
    }
  }
}
