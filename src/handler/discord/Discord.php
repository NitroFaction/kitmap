<?php

namespace NCore\handler\discord;

use NCore\Base;
use NCore\task\async\SendMessageTask;

class Discord
{
    const USERNAME = "Nitro";

    public static function send($data, $webhook): void
    {
        $content = null;

        if ($data instanceof Message) {
            $content = json_encode([
                "username" => self::USERNAME,
                "content" => $data->getMessage()
            ]);
        }

        if ($data instanceof EmbedBuilder) {
            $content = $data->getContent();
        }

        if (!is_null($content)) {
            Base::getInstance()->getServer()->getAsyncPool()->submitTask(new SendMessageTask($webhook, $content));
        }
    }
}