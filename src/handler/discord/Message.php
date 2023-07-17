<?php

namespace Kitmap\handler\discord;

class Message
{
    public function __construct(string $webhookUrl, private readonly string $message)
    {
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}