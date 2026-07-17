<?php

namespace App\Services\Chatbot\Tools;

use App\Models\User;

interface ToolInterface
{
    /**
     * Nombre único de la tool (usado en function calling).
     */
    public function name(): string;

    /**
     * Descripción para el LLM.
     */
    public function description(): string;

    /**
     * JSON Schema de parámetros.
     */
    public function parameters(): array;

    /**
     * Ejecuta la tool con los argumentos validados.
     */
    public function execute(User $user, array $args, array $context): ToolResult;
}
