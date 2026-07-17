<?php

namespace App\Policies;

use App\Models\ChatbotConversation;
use App\Models\User;

class ChatbotConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, ChatbotConversation $conversation): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, ChatbotConversation $conversation): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, ChatbotConversation $conversation): bool
    {
        return false;
    }
}
