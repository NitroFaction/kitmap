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

class Unclaim extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "unclaim", "Supprimer votre claim actuel");
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
            } else if (is_null(Cache::$factions[$faction]["claim"])) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas supprimer votre claim si vous n'en avez pas");
                return;
            }

            $plot = Cache::$factions[$faction]["claim"];

            Cache::$factions[$faction]["claim"] = null;
            Cache::$plots[$plot]["faction"] = null;

            Cache::$factions[$faction]["logs"][time()] = "§e" . $sender->getName() . " §funclaim l'ancien claim";
            FactionAPI::broadcastMessage($faction, "§e[§fF§r§e] §fVotre faction vient de supprimer votre claim actuel");
        }
    }

    protected function prepare(): void
    {
    }
}