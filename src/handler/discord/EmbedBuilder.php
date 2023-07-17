<?php

namespace NCore\handler\discord;

class EmbedBuilder
{
    public const baseEmbed = [
        "username" => Discord::USERNAME,
        "embeds" => [
            [
                "color" => 16733525,
                "fields" => [],
                "footer" => []
            ]
        ]
    ];

    private array $content;

    public function __construct()
    {
        $this->content = self::baseEmbed;
    }

    public function setTitle(string $title): self
    {
        $this->content["embeds"][0]["title"] = $title;
        return $this;
    }

    public function setColor(int $color): self
    {
        $this->content["embeds"][0]["color"] = $color;
        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->content["embeds"][0]["description"] = $description;
        return $this;
    }

    public function getContent(): bool|string
    {
        return json_encode($this->content);
    }
}