<?php

namespace App\Bot\Commands;

use TelegramBot\Api\Types\Message;
use TelegramBot\Api\BotApi;

class HelpCommands
{
    public function show(Message $message, BotApi $telegram, ?array $user)
    {
        $chatId = (string) $message->getChat()->getId();
        
        $helpText = "🤖 *Mastermind Bot - Comandos Disponíveis*\n\n";
        
        $helpText .= "📋 *Comandos de Autenticação:*\n";
        $helpText .= "• `/start` - Iniciar o bot\n";
        $helpText .= "• `/login` - Fazer login na sua conta\n";
        $helpText .= "• `/register` - Criar uma nova conta\n";
        $helpText .= "• `/logout` - Sair da sua conta\n";
        $helpText .= "• `/ajuda` - Mostrar esta lista de comandos\n\n";
        
        $helpText .= "📂 *Comandos de Categorias:*\n";
        $helpText .= "• `/categorias` - Listar suas categorias\n";
        $helpText .= "• `/add_categoria` - Criar uma nova categoria\n";
        $helpText .= "• `/edit_categoria` - Editar uma categoria existente\n";
        $helpText .= "• `/del_categoria` - Excluir uma categoria\n\n";
        
        $helpText .= "📝 *Comandos de Tarefas:*\n";
        $helpText .= "• `/tarefas` - Listar todas as suas tarefas\n";
        $helpText .= "• `/tarefas hoje` - Tarefas para hoje\n";
        $helpText .= "• `/tarefas semana` - Tarefas da semana\n";
        $helpText .= "• `/tarefas mes` - Tarefas do mês\n";
        $helpText .= "• `/tarefas atrasadas` - Tarefas em atraso\n";
        $helpText .= "• `/add_tarefa` - Criar uma nova tarefa\n";
        $helpText .= "• `/edit_tarefa` - Editar uma tarefa existente\n";
        $helpText .= "• `/del_tarefa` - Excluir uma tarefa\n";
        $helpText .= "• `/done <id>` - Marcar tarefa como concluída\n\n";
        
        $helpText .= "💡 *Dicas:*\n";
        $helpText .= "• Use `/login` para acessar suas tarefas e categorias\n";
        $helpText .= "• Crie categorias antes de adicionar tarefas\n";
        $helpText .= "• Use `/done <número>` para marcar tarefas como concluídas\n";
        $helpText .= "• Todas as operações são sincronizadas com sua conta web\n\n";
        
        $helpText .= "🔗 *Sobre:*\n";
        $helpText .= "Este bot está conectado ao Mastermind, seu gerenciador de tarefas pessoal.";
        
        $telegram->sendMessage($chatId, $helpText, 'Markdown');
    }
} 