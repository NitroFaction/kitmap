<?php

namespace Kitmap\task;

use Kitmap\command\staff\op\Stop;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\scheduler\Task;

class StopTask extends Task
{
    public function __construct(private int $time)
    {
    }

    public function onRun(): void
    {
        Stop::$task = $this;

        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Redémarrage du serveur dans §e" . max(0, $this->time) . " §fsecondes");
        Main::getInstance()->getServer()->broadcastPopup(Util::PREFIX . "Redémarrage du serveur dans §e" . max(0, $this->time) . " §fsecondes");

        $this->time--;

        if ($this->time <= 0) {
            Main::getInstance()->getServer()->shutdown();
        }
    }
}