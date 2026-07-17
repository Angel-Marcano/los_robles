<?php

namespace App\Console\Commands;

use App\Services\Chatbot\DeepSeekClient;
use Illuminate\Console\Command;

class ChatbotPing extends Command
{
    protected $signature = 'chatbot:ping';
    protected $description = 'Valida conectividad con DeepSeek';

    public function handle()
    {
        try {
            $client = app(DeepSeekClient::class);
            $reply = $client->ping();
            $this->info('Respuesta de DeepSeek: ' . $reply);
            return 0;
        } catch (\Throwable $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}
