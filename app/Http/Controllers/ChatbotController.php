<?php

namespace App\Http\Controllers;

use App\Services\Chatbot\ChatOrchestrator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    protected ChatOrchestrator $orchestrator;

    public function __construct(ChatOrchestrator $orchestrator)
    {
        $this->orchestrator = $orchestrator;
    }

    public function message(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $request->validate([
            'message' => 'required|string|max:1000',
            'session_id' => 'nullable|string|max:64',
        ]);

        $key = 'chatbot:' . $user->id;
        $limit = (int) config('services.chatbot.rate_limit_per_minute', 10);
        if (RateLimiter::tooManyAttempts($key, $limit)) {
            return response()->json(['error' => 'Demasiados mensajes. Espera un minuto.'], 429);
        }
        RateLimiter::hit($key, 60);

        $sessionId = $request->input('session_id') ?: (string) Str::uuid();

        try {
            $response = $this->orchestrator->handle(
                $user,
                $request->input('message'),
                $sessionId,
                'web'
            );
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Ocurrió un error procesando tu mensaje.'], 500);
        }

        return response()->json($response);
    }

    public function history(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $sessionId = $request->input('session_id');
        $query = \App\Models\ChatbotConversation::forUser($user->id)
            ->orderByDesc('created_at')
            ->limit(50);

        if ($sessionId) {
            $query->forSession($sessionId);
        }

        return response()->json($query->get(['id', 'session_id', 'intent', 'input_raw', 'output_raw', 'created_at']));
    }
}
