<?php

namespace NCore\command\sub\faction;

use CortexPE\Commando\BaseSubCommand;
use NCore\Base;
use NCore\handler\Cache;
use NCore\handler\FactionAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class Sethome extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "sethome", "Défini un point de téléportation commun à une faction");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);
            $faction = $session->data["player"]["faction"];

            $permission = FactionAPI::hasPermission($sender, $this->getName());

            if (is_null($permission)) {
                $sender->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction");
                return;
            } else if (!$permission) {
                $sender->sendMessage(Util::PREFIX . "Vous ne possèdez pas les permissions necessaire dans votre faction pour faire cela");
                return;
            } else if (FactionAPI::inPlot($sender->getPosition()->getFloorX(), $sender->getPosition()->getFloorZ())[1] !== $faction) {
                $sender->sendMessage(Util::PREFIX . "Vous devez être dans votre claim pour faire cela");
                return;
            }

            Cache::$factions[$faction]["home"] = round($sender->getPosition()->x) . ":" . round($sender->getPosition()->y) . ":" . round($sender->getPosition()->z);
            Cache::$factions[$faction]["logs"][time()] = "§e" . $sender->getName() . " §fsethome (§e" . Cache::$factions[$faction]["home"] . "§f)";

            $sender->sendMessage(Util::PREFIX . "Vous venez de définir le point de téléportation de votre home");
        }
    }

    protected function prepare(): void
    {
    }
}