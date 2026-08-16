<?php

namespace App\Services\Chatbot;

use App\Models\ChatbotConversation;
use App\Models\User;
use App\Services\Chatbot\Tools\CreateSupportTicketTool;
use App\Services\Chatbot\Tools\GetCondoRulesTool;
use App\Services\Chatbot\Tools\GetDueDateTool;
use App\Services\Chatbot\Tools\GetInvoiceBalanceTool;
use App\Services\Chatbot\Tools\GetPaymentStatusTool;
use App\Services\Chatbot\Tools\ReportPaymentTool;
use App\Services\Chatbot\Tools\ToolResult;
use Illuminate\Support\Facades\Log;

class ChatOrchestrator
{
    protected DeepSeekClient $client;
    protected IntentClassifier $classifier;
    protected ContextBuilder $contextBuilder;
    protected ConfirmationManager $confirmationManager;
    protected HumanHandoffService $handoffService;
    protected HistoryLoader $historyLoader;

    protected array $tools = [];

    public function __construct(
        DeepSeekClient $client,
        IntentClassifier $classifier,
        ContextBuilder $contextBuilder,
        ConfirmationManager $confirmationManager,
        HumanHandoffService $handoffService,
        HistoryLoader $historyLoader
    ) {
        $this->client = $client;
        $this->classifier = $classifier;
        $this->contextBuilder = $contextBuilder;
        $this->confirmationManager = $confirmationManager;
        $this->handoffService = $handoffService;
        $this->historyLoader = $historyLoader;

        $this->tools = [
            'get_invoice_balance' => app(GetInvoiceBalanceTool::class),
            'get_due_date' => app(GetDueDateTool::class),
            'get_payment_status' => app(GetPaymentStatusTool::class),
            'get_condo_rules' => app(GetCondoRulesTool::class),
            'report_payment' => app(ReportPaymentTool::class),
            'create_support_ticket' => app(CreateSupportTicketTool::class),
        ];
    }

    /**
     * Procesa un mensaje del usuario y devuelve respuesta estructurada.
     */
    public function handle(User $user, string $message, string $sessionId, string $channel = 'web'): array
    {
        $start = microtime(true);
        $context = $this->contextBuilder->build($user);
        $currentCondo = app()->bound('currentCondominium') ? app('currentCondominium') : null;
        $context['condominium_id'] = optional($currentCondo)->id;
        $sanitizedInput = PiiSanitizer::sanitize($message);

        // 0. Guardrails: dominio y límites de operaciones
        $guardrails = Guardrails::validate($message, $sessionId, $user->id);
        if (!$guardrails['allowed']) {
            $conversation = $this->saveConversation(
                $user, $sessionId, $channel, $message, $sanitizedInput, 'blocked_by_guardrails', $context,
                [], ['guardrails' => $guardrails], $guardrails['reason'], round((microtime(true) - $start) * 1000, 2)
            );
            return $this->response($guardrails['reason'], $conversation);
        }

        // 1. Verificar escalamiento a humano
        if ($this->handoffService->isHandoffRequest($message)) {
            $conversation = $this->saveConversation($user, $sessionId, $channel, $message, $sanitizedInput, 'human_handoff', $context, [], [], null, 0);
            $this->handoffService->escalate($conversation, $user);

            return $this->response('He derivado tu solicitud a un administrador humano. Te contactarán pronto.', $conversation);
        }

        // 2. Verificar acción pendiente de confirmación
        $pendingAction = $this->confirmationManager->getPendingAction($user, $sessionId);
        if ($pendingAction) {
            return $this->handleConfirmation($user, $message, $sessionId, $pendingAction, $context, $start);
        }

        // 3. Cargar historial reciente para contexto (máximo 10 mensajes)
        $history = $this->historyLoader->load($user, $sessionId, 10);

        // 4. Clasificar intención
        $classification = $this->classifier->classify($message, $history);
        $intent = $classification['intent'] ?? 'unknown';
        $entities = $classification['entities'] ?? [];

        // 5. Ejecutar según intención
        $toolResult = null;
        $toolsCalled = [];
        $actionsExecuted = [];

        switch ($intent) {
            case 'balance':
                $toolResult = $this->tools['get_invoice_balance']->execute($user, $entities, $context);
                $toolsCalled[] = 'get_invoice_balance';
                break;
            case 'due_date':
                $toolResult = $this->tools['get_due_date']->execute($user, $entities, $context);
                $toolsCalled[] = 'get_due_date';
                break;
            case 'payment_status':
                $toolResult = $this->tools['get_payment_status']->execute($user, $entities, $context);
                $toolsCalled[] = 'get_payment_status';
                break;
            case 'report_payment':
                $toolResult = $this->tools['report_payment']->execute($user, $entities, $context);
                $toolsCalled[] = 'report_payment';
                break;
            case 'create_ticket':
                $toolResult = $this->tools['create_support_ticket']->execute($user, $entities, $context);
                $toolsCalled[] = 'create_support_ticket';
                break;
            case 'faq':
                $toolResult = $this->tools['get_condo_rules']->execute($user, ['topic' => $entities['topic'] ?? 'general'], $context);
                $toolsCalled[] = 'get_condo_rules';
                break;
            case 'human_handoff':
                $conversation = $this->saveConversation($user, $sessionId, $channel, $message, $sanitizedInput, $intent, $context, $toolsCalled, $actionsExecuted, null, 0);
                $this->handoffService->escalate($conversation, $user);
                return $this->response('He derivado tu solicitud a un administrador humano.', $conversation);
            default:
                $toolResult = ToolResult::ok('No estoy seguro de cómo ayudarte. Puedes preguntarme sobre tu saldo, vencimientos, estado de pagos, reportar un pago o crear un ticket de soporte.');
                break;
        }

        // 6. Si la tool pide confirmación, guardar acción pendiente
        if ($toolResult && $toolResult->confirmationPrompt) {
            $this->confirmationManager->storePendingAction($user, $sessionId, $toolResult->data);
            $conversation = $this->saveConversation(
                $user, $sessionId, $channel, $message, $sanitizedInput, $intent, $context,
                $toolsCalled, $actionsExecuted, $toolResult->confirmationPrompt, round((microtime(true) - $start) * 1000, 2)
            );
            return $this->response($toolResult->confirmationPrompt, $conversation, true);
        }

        // 7. Si la tool pide más información
        if ($toolResult && !$toolResult->success && empty($toolResult->confirmationPrompt)) {
            $conversation = $this->saveConversation(
                $user, $sessionId, $channel, $message, $sanitizedInput, $intent, $context,
                $toolsCalled, $actionsExecuted, $toolResult->message, round((microtime(true) - $start) * 1000, 2)
            );
            return $this->response($toolResult->message, $conversation);
        }

        // 8. Generar respuesta final con DeepSeek (para lectura/FAQ)
        $finalMessage = $this->generateFinalResponse($message, $toolResult, $context, $history);

        if ($toolResult && $toolResult->success && !empty($toolResult->data)) {
            $actionsExecuted = $toolResult->data;
        }

        $conversation = $this->saveConversation(
            $user, $sessionId, $channel, $message, $sanitizedInput, $intent, $context,
            $toolsCalled, $actionsExecuted, $finalMessage, round((microtime(true) - $start) * 1000, 2)
        );

        return $this->response($finalMessage, $conversation);
    }

    protected function handleConfirmation(User $user, string $message, string $sessionId, array $pendingAction, array $context, float $start): array
    {
        $status = $this->confirmationManager->parseConfirmation($message);

        if ($status === 'rejected') {
            $this->confirmationManager->resolvePendingAction($user, $sessionId);
            $conversation = $this->saveConversation(
                $user, $sessionId, 'web', $message, PiiSanitizer::sanitize($message), 'confirmation_rejected', $context,
                [], [], 'Acción cancelada.', round((microtime(true) - $start) * 1000, 2)
            );
            return $this->response('Acción cancelada. ¿Hay algo más en lo que pueda ayudarte?', $conversation);
        }

        if ($status !== 'confirmed') {
            return $this->response('Por favor responde "sí" para confirmar o "no" para cancelar.', null, true);
        }

        // Ejecutar acción confirmada
        $toolName = $pendingAction['tool'] ?? null;
        $toolResult = null;
        $actionsExecuted = [];

        if ($toolName === 'report_payment') {
            $toolResult = $this->tools['report_payment']->confirm($user, $pendingAction);
            $actionsExecuted = $toolResult->data;
        } elseif ($toolName === 'create_support_ticket') {
            $toolResult = $this->tools['create_support_ticket']->confirm($user, $pendingAction);
            $actionsExecuted = $toolResult->data;
        }

        $this->confirmationManager->resolvePendingAction($user, $sessionId);

        $conversation = $this->saveConversation(
            $user, $sessionId, 'web', $message, PiiSanitizer::sanitize($message), $toolName . '_confirmed', $context,
            [$toolName], $actionsExecuted, $toolResult->message, round((microtime(true) - $start) * 1000, 2)
        );

        return $this->response($toolResult->message, $conversation);
    }

    protected function generateFinalResponse(string $userMessage, ?ToolResult $toolResult, array $context, array $history = []): string
    {
        if (!$toolResult) {
            return 'No pude procesar tu solicitud. Intenta de otra forma.';
        }

        if (!$toolResult->success) {
            return $toolResult->message;
        }

        // Para respuestas simples de tools de lectura, devolvemos directamente el mensaje de la tool
        // para evitar una llamada extra al LLM y reducir costo/latencia.
        // Si en el futuro se desea respuesta más natural, usar $history + DeepSeek aquí.
        return $toolResult->message;
    }

    protected function saveConversation(
        User $user,
        string $sessionId,
        string $channel,
        string $inputRaw,
        string $inputSanitized,
        string $intent,
        array $context,
        array $toolsCalled,
        array $actionsExecuted,
        ?string $output,
        float $durationMs,
        ?string $model = null
    ): ChatbotConversation {
        return ChatbotConversation::create([
            'user_id' => $user->id,
            'condominium_id' => $context['condominium_id'] ?? null,
            'session_id' => $sessionId,
            'channel' => $channel,
            'intent' => $intent,
            'prompt_version' => '1.0',
            'input_raw' => $inputRaw,
            'input_sanitized' => $inputSanitized,
            'output_raw' => $output,
            'output_sanitized' => PiiSanitizer::sanitizeOutput($output ?? ''),
            'tools_called' => $toolsCalled,
            'actions_executed' => $actionsExecuted,
            'context' => $context,
            'duration_ms' => (int) $durationMs,
            'model' => $model,
        ]);
    }

    protected function response(string $message, ?ChatbotConversation $conversation = null, bool $requiresConfirmation = false): array
    {
        return [
            'message' => $message,
            'session_id' => $conversation?->session_id,
            'conversation_id' => $conversation?->id,
            'requires_confirmation' => $requiresConfirmation,
        ];
    }
}
