<?php

namespace App\Bot;

use TelegramBot\Api\BotApi;
use TelegramBot\Api\Exception;
use TelegramBot\Api\InvalidJsonException;
use TelegramBot\Api\HttpException;

class Bot
{
  private BotApi $telegram;
  private CommandHandler $commandHandler;
  private int $maxRetries = 5;
  private int $retryDelay = 5;

  public function __construct()
  {
    $botToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? null;

    if (!$botToken) {
      throw new \Exception("TELEGRAM_BOT_TOKEN não definido no .env");
    }

    // Configurar timeout personalizado para a biblioteca do Telegram
    $this->telegram = new BotApi($botToken);
    $this->telegram->setCurlOption(CURLOPT_TIMEOUT, 60); // 60 segundos
    $this->telegram->setCurlOption(CURLOPT_CONNECTTIMEOUT, 10); // 10 segundos para conexão
    $this->telegram->setCurlOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    
    $this->commandHandler = new CommandHandler();
  }

  public function listen()
  {
    echo "Bot Mastermind running...\n";

    // Verificar se o token é válido antes de iniciar
    if (!$this->validateBotToken()) {
      echo "❌ Token do bot inválido. Verifique o TELEGRAM_BOT_TOKEN no arquivo .env\n";
      return;
    }

    echo "✅ Token do bot validado com sucesso!\n";

    $offset = 0;
    $consecutiveErrors = 0;
    $maxConsecutiveErrors = 10;

    while (true) {
      try {
        // Reduzir o timeout para getUpdates para evitar travamentos longos
        $updates = $this->telegram->getUpdates($offset, 100, 10); // Reduzido de 30 para 10 segundos
        
        // Reset contador de erros se a requisição foi bem-sucedida
        $consecutiveErrors = 0;
        
        foreach ($updates as $update) {
          $offset = $update->getUpdateId() + 1;
          $message = $update->getMessage();

          if ($message) {
            try {
              $this->commandHandler->handle($message, $this->telegram);
            } catch (\Exception $e) {
              echo "Erro ao processar mensagem: " . $e->getMessage() . "\n";
            }
          }
        }

        // Aguardar um pouco antes da próxima requisição
        sleep(1);
        
      } catch (HttpException $e) {
        $consecutiveErrors++;
        echo "Erro HTTP do Telegram (tentativa {$consecutiveErrors}/{$maxConsecutiveErrors}): " . $e->getMessage() . "\n";
        
        if ($consecutiveErrors >= $maxConsecutiveErrors) {
          echo "❌ Muitos erros consecutivos. Reiniciando bot em 30 segundos...\n";
          sleep(30);
          $consecutiveErrors = 0;
        } else {
          sleep($this->retryDelay);
        }
        
      } catch (InvalidJsonException $e) {
        echo "Erro de JSON inválido: " . $e->getMessage() . "\n";
        sleep($this->retryDelay);
        
      } catch (Exception $e) {
        $consecutiveErrors++;
        echo "Erro do Telegram (tentativa {$consecutiveErrors}/{$maxConsecutiveErrors}): " . $e->getMessage() . "\n";
        
        if ($consecutiveErrors >= $maxConsecutiveErrors) {
          echo "❌ Muitos erros consecutivos. Reiniciando bot em 30 segundos...\n";
          sleep(30);
          $consecutiveErrors = 0;
        } else {
          sleep($this->retryDelay);
        }
        
      } catch (\Exception $e) {
        $consecutiveErrors++;
        echo "Erro inesperado (tentativa {$consecutiveErrors}/{$maxConsecutiveErrors}): " . $e->getMessage() . "\n";
        
        if ($consecutiveErrors >= $maxConsecutiveErrors) {
          echo "❌ Muitos erros consecutivos. Reiniciando bot em 30 segundos...\n";
          sleep(30);
          $consecutiveErrors = 0;
        } else {
          sleep($this->retryDelay);
        }
      }
    }
  }

  private function validateBotToken(): bool
  {
    try {
      // Tentar obter informações do bot para validar o token
      $botInfo = $this->telegram->getMe();
      echo "✅ Bot conectado: @{$botInfo->getUsername()}\n";
      return true;
    } catch (\Exception $e) {
      echo "❌ Erro ao validar token: " . $e->getMessage() . "\n";
      return false;
    }
  }
}
