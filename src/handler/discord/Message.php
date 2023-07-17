<?php

namespace NCore\handler\discord;

class Message
{
    public function __construct(string $webhookUrl, private string $message)
    {
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}