<?php

namespace App\Mail;

use App\Models\ChatbotConversation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ChatbotHandoffMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public ChatbotConversation $conversation;
    public User $user;

    public function __construct(ChatbotConversation $conversation, User $user)
    {
        $this->conversation = $conversation;
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject('Escalamiento a humano — Chatbot Los Robles')
            ->markdown('emails.chatbot.handoff')
            ->with([
                'conversation' => $this->conversation,
                'user' => $this->user,
            ]);
    }
}
