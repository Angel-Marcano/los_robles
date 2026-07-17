<?php

namespace App\Services\Chatbot;

class ChatbotConfig
{
    public static function systemPrompt(): string
    {
        return <<<'PROMPT'
Eres "RobleBot", el asistente virtual del sistema de administración de condominios Los Robles.

Tu trabajo es ayudar a propietarios, residentes y administradores con preguntas frecuentes y acciones simples relacionadas con su condominio.

REGLAS IMPORTANTES:
1. Solo puedes responder sobre temas del condominio: facturas, pagos, morosidad, apartamentos, reglas internas, soporte.
2. Nunca inventes datos. Si necesitas información de la cuenta del usuario, usa las herramientas disponibles.
3. No reveles información de otros propietarios ni apartamentos.
4. Para acciones que modifican datos (reportar pago, crear ticket), siempre pide confirmación explícita.
5. Si no entiendes la solicitud o es sensible (reclamos legales, cambios de propietario), ofrece derivar con un administrador humano.
6. Responde en español, de forma clara, corta y útil.
7. No ejecutes código ni generes consultas SQL.
PROMPT;
    }

    public static function intentClassificationPrompt(): string
    {
        return <<<'PROMPT'
Clasifica la intención del siguiente mensaje de un usuario del sistema de condominios Los Robles.

Intenciones permitidas:
- faq: preguntas generales sobre el condominio, reglas, horarios, contactos.
- balance: consultar saldo pendiente, cuánto debe, estado de deuda.
- due_date: consultar fecha de vencimiento de factura.
- payment_status: saber si un pago fue aprobado/rechazado o está pendiente.
- report_payment: el usuario quiere reportar un pago realizado.
- create_ticket: el usuario quiere crear un ticket de soporte.
- human_handoff: quiere hablar con un humano, hace un reclamo grave o el bot no puede ayudar.
- unknown: no encaja en ninguna de las anteriores.

Responde ÚNICAMENTE con un JSON válido con esta estructura:
{"intent": "nombre_intencion", "confidence": "high|medium|low", "entities": {"apartment_id": null, "invoice_id": null, "amount_usd": null, "amount_ves": null, "category": null, "description": null}}

No agregues explicaciones ni texto fuera del JSON.
PROMPT;
    }

    public static function allowedIntents(): array
    {
        return [
            'faq',
            'balance',
            'due_date',
            'payment_status',
            'report_payment',
            'create_ticket',
            'human_handoff',
            'unknown',
        ];
    }

    public static function knowledgeBasePath(): string
    {
        return resource_path('chatbot/kb');
    }

    public static function maxHistoryMessages(): int
    {
        return (int) config('services.chatbot.max_history_messages', 20);
    }

    public static function rateLimitPerMinute(): int
    {
        return (int) config('services.chatbot.rate_limit_per_minute', 10);
    }
}
