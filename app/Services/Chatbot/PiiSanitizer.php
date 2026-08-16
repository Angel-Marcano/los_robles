<?php

namespace App\Services\Chatbot;

class PiiSanitizer
{
    /**
     * Reemplaza PII común por tokens genéricos antes de enviar a un LLM externo.
     */
    public static function sanitize(string $text): string
    {
        // Correos electrónicos
        $text = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '[EMAIL]', $text);

        // Teléfonos venezolanos básicos (04xx-xxxxxxx, +58, etc.)
        $text = preg_replace('/(\+?58[-\s]?)?(0?4\d{2}[-\s]?\d{7})/', '[TELEFONO]', $text);

        // Cédulas venezolanas (V-12345678, E-12345678, 12345678)
        $text = preg_replace('/\b(V|E)-?\d{6,9}\b/', '[CEDULA]', $text);

        // Nombres propios simples (opcional, conservador): palabras capitalizadas sueltas
        // Se desactiva por defecto porque puede dañar frases comunes. Activar con cuidado.
        // $text = preg_replace('/\b[A-Z][a-z]+\b/', '[NOMBRE]', $text);

        return $text;
    }

    /**
     * Limpia output del LLM si contiene tokens que no deberían mostrarse.
     */
    public static function sanitizeOutput(string $text): string
    {
        return self::sanitize($text);
    }
}
