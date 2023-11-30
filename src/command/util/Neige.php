<?php

namespace Kitmap\command\util;

use CortexPE\Commando\BaseCommand;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Neige extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "neige",
            "Activer/désactiver la neige"
        );

        $this->setAliases(["meteo"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->data["meteo"]) {
                $session->data["meteo"] = false;
                $packet = LevelEventPacket::create(LevelEvent::STOP_RAIN, 10000, null);
                $sender->sendMessage(Util::PREFIX . "Vous venez de désactiver la neige");
            } else {
                $session->data["meteo"] = true;
                $packet = LevelEventPacket::create(LevelEvent::START_RAIN, 10000, null);
                $sender->sendMessage(Util::PREFIX . "Vous venez d'activer la neige");
            }

            $sender->getNetworkSession()->sendDataPacket($packet);

        }
    }

    protected function prepare(): void
    {
    }
}