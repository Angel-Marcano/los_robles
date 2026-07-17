<?php

namespace App\Services\Chatbot;

use Illuminate\Support\Facades\Cache;

class Guardrails
{
    /**
     * Palabras clave que indican que el mensaje está dentro del dominio del condominio.
     * Si el mensaje coincide con al menos una, se considera dentro de dominio.
     */
    public static function domainKeywords(): array
    {
        return [
            // Facturación y pagos
            'factura', 'facturación', 'pago', 'pagos', 'saldo', 'deuda', 'debo', 'mora', 'recargo',
            'vencimiento', 'vence', 'fecha de pago', 'cuota', 'alícuota', 'gasto común', 'gastos comunes',
            'reportar pago', 'reporte de pago', 'comprobante', 'transferencia', 'zelle', 'pago móvil',
            'bolívares', 'ves', 'usd', 'dólares', 'tasa', 'cambio', 'divisa',

            // Propiedad y condominio
            'apartamento', 'apto', 'apt', 'torre', 'condominio', 'propietario', 'residente', 'inquilino',
            'ownership', 'propiedad', 'alquiler', 'arrendamiento',

            // Reglas y convivencia
            'regla', 'reglamento', 'norma', 'horario', 'ruido', 'mascota', 'estacionamiento', 'visitante',
            'portería', 'portero', 'áreas comunes', 'ascensor', 'elevador', 'seguridad', 'emergencia',

            // Soporte y administración
            'ticket', 'soporte', 'reclamo', 'queja', 'incidencia', 'falla', 'mantenimiento', 'avería',
            'administrador', 'administración', 'humano', 'hablar con', 'mesa de ayuda', 'ayuda',

            // Interacción general permitida
            'hola', 'buenos', 'buenas', 'tardes', 'noches', 'días', 'gracias', 'adiós', 'chao',
            'ok', 'vale', 'sí', 'si', 'no', 'confirmar', 'cancelar', 'entendido', 'claro',
        ];
    }

    /**
     * Patrones que activan bloqueo inmediato (jailbreak, off-domain, peligroso).
     */
    public static function blockedPatterns(): array
    {
        return [
            // Jailbreak / prompt injection
            'ignora (todas )?las instrucciones',
            'ignora (todo )?lo anterior',
            'modo desarrollador',
            'modo admin',
            'modo dios',
            'modo root',
            'eres ahora',
            'actúa como si',
            'actua como si',
            'simula ser',
            'pretende ser',
            'dan mode',
            'jailbreak',
            'prompt injection',
            'system prompt',
            'revela tu',
            'muéstrame tu',
            'muestrame tu',
            'contraseña',
            'password',
            'api key',
            'apikey',
            'token secreto',
            'llave secreta',
            'clave secreta',
            'base de datos',
            'tabla de',
            'consulta sql',
            'query sql',
            'ejecuta sql',
            'run sql',
            'drop table',
            'delete from',
            'update .* set',

            // Temas fuera de dominio comunes
            'clima',
            'pronóstico',
            'pronostico',
            'noticias',
            'deportes',
            'fútbol',
            'beisbol',
            'béisbol',
            'política',
            'politica',
            'elecciones',
            'religión',
            'religion',
            'sexo',
            'porno',
            'apuesta',
            'casino',
            'criptomoneda',
            'bitcoin',
            'trading',
            'compra acciones',
            'programar',
            'código',
            'codigo',
            'escribe un script',
            'hackear',
            'hack',
            'virus',
            'malware',
        ];
    }

    /**
     * Verifica si un mensaje está dentro del dominio permitido y no es jailbreak.
     * Devuelve array con 'allowed' (bool), 'reason' (string|null) y 'risk' (low|medium|high).
     */
    public static function check(string $message): array
    {
        $lower = mb_strtolower($message);

        // 1. Bloqueo inmediato por patrones peligrosos o fuera de dominio
        foreach (self::blockedPatterns() as $pattern) {
            if (preg_match('/' . $pattern . '/iu', $lower)) {
                return [
                    'allowed' => false,
                    'reason' => 'Tu mensaje contiene términos que no puedo procesar. Solo puedo ayudarte con temas de tu condominio.',
                    'risk' => 'high',
                    'trigger' => $pattern,
                ];
            }
        }

        // 2. Verificación de dominio
        $domainMatches = 0;
        foreach (self::domainKeywords() as $keyword) {
            if (str_contains($lower, mb_strtolower($keyword))) {
                $domainMatches++;
            }
        }

        if ($domainMatches > 0) {
            return [
                'allowed' => true,
                'reason' => null,
                'risk' => 'low',
                'trigger' => null,
            ];
        }

        // 3. Mensaje ambiguo: corto o sin palabras de dominio
        // Permitimos saludos muy cortos y preguntas simples, pero marcamos riesgo medio.
        if (mb_strlen(trim($message)) <= 20) {
            return [
                'allowed' => true,
                'reason' => null,
                'risk' => 'medium',
                'trigger' => 'short_message',
            ];
        }

        return [
            'allowed' => false,
            'reason' => 'Solo puedo ayudarte con temas relacionados a tu condominio: facturas, pagos, apartamentos, reglas y soporte. Si necesitas algo fuera de ese alcance, te puedo derivar con un administrador.',
            'risk' => 'medium',
            'trigger' => 'out_of_domain',
        ];
    }

    /**
     * Incrementa el contador de operaciones de la sesión y del usuario.
     * Devuelve true si aún está dentro del límite.
     */
    public static function checkOperationLimit(string $sessionId, int $userId): array
    {
        $maxPerSession = (int) config('services.chatbot.max_operations_per_session', 30);
        $maxPerUserPerHour = (int) config('services.chatbot.max_operations_per_user_hour', 60);

        $sessionKey = 'chatbot:ops:session:' . $sessionId;
        $userKey = 'chatbot:ops:user:' . $userId;

        $sessionOps = Cache::get($sessionKey, 0) + 1;
        $userOps = Cache::get($userKey, 0) + 1;

        Cache::put($sessionKey, $sessionOps, now()->addHours(4));
        Cache::put($userKey, $userOps, now()->addHour());

        if ($sessionOps > $maxPerSession) {
            return [
                'allowed' => false,
                'reason' => 'Has alcanzado el límite de interacciones por sesión. Por favor descansa o contacta a administración.',
                'session_ops' => $sessionOps,
                'user_ops' => $userOps,
            ];
        }

        if ($userOps > $maxPerUserPerHour) {
            return [
                'allowed' => false,
                'reason' => 'Has alcanzado el límite de interacciones por hora. Intenta más tarde.',
                'session_ops' => $sessionOps,
                'user_ops' => $userOps,
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
            'session_ops' => $sessionOps,
            'user_ops' => $userOps,
        ];
    }

    /**
     * Ejecuta todas las validaciones de guardrails y devuelve un resultado unificado.
     */
    public static function validate(string $message, string $sessionId, int $userId): array
    {
        $domainCheck = self::check($message);
        if (!$domainCheck['allowed']) {
            return $domainCheck;
        }

        $limitCheck = self::checkOperationLimit($sessionId, $userId);
        if (!$limitCheck['allowed']) {
            return $limitCheck;
        }

        return [
            'allowed' => true,
            'reason' => null,
            'risk' => $domainCheck['risk'],
            'session_ops' => $limitCheck['session_ops'],
            'user_ops' => $limitCheck['user_ops'],
            'trigger' => $domainCheck['trigger'],
        ];
    }
}
