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

class Delhome extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "delhome", "Supprime le point de téléportation commun à une faction");
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
            }

            Cache::$factions[$faction]["home"] = "0:0:0";
            Cache::$factions[$faction]["logs"][time()] = "§e" . $sender->getName() . " §fsupprime le f home";

            $sender->sendMessage(Util::PREFIX . "Vous venez de supprimer le point de téléportation de votre home");
        }
    }

    protected function prepare(): void
    {
    }
}