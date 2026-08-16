<?php

namespace App\Http\Controllers;

use App\Models\ChatbotConversation;
use Illuminate\Http\Request;

class ChatbotAdminController extends Controller
{
    public function conversations(Request $request)
    {
        $this->authorize('viewAny', ChatbotConversation::class);

        $q = ChatbotConversation::query()
            ->with('user:id,name,email')
            ->orderByDesc('created_at');

        if ($request->filled('needs_human')) {
            $q->where('needs_human', true);
        }
        if ($request->filled('session_id')) {
            $q->where('session_id', $request->input('session_id'));
        }

        $conversations = $q->paginate(30)->appends($request->query());

        return view('chatbot.admin.conversations', compact('conversations'));
    }

    public function conversation(ChatbotConversation $conversation)
    {
        $this->authorize('view', $conversation);

        $history = ChatbotConversation::forSession($conversation->session_id)
            ->orderBy('created_at')
            ->get();

        return view('chatbot.admin.conversation', compact('conversation', 'history'));
    }

    public function resolve(ChatbotConversation $conversation)
    {
        $this->authorize('update', $conversation);

        $conversation->update(['needs_human' => false]);

        return back()->with('status', 'Conversación marcada como resuelta.');
    }
}
