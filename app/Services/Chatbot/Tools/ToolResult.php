<?php

namespace App\Services\Chatbot\Tools;

class ToolResult
{
    public bool $success;
    public string $message;
    public array $data;
    public ?string $confirmationPrompt;

    public function __construct(bool $success, string $message, array $data = [], ?string $confirmationPrompt = null)
    {
        $this->success = $success;
        $this->message = $message;
        $this->data = $data;
        $this->confirmationPrompt = $confirmationPrompt;
    }

    public static function ok(string $message, array $data = []): self
    {
        return new self(true, $message, $data);
    }

    public static function error(string $message, array $data = []): self
    {
        return new self(false, $message, $data);
    }

    public static function needsConfirmation(string $prompt, array $data = []): self
    {
        return new self(true, '', $data, $prompt);
    }

    public static function needsInfo(string $question, array $data = []): self
    {
        return new self(false, $question, $data);
    }
}
