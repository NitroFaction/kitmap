<?php

namespace Kitmap\handler\discord;

readonly class Message
{
    public function __construct(string $webhookUrl, private string $message)
    {
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}