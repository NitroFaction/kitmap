<?php

namespace NCore\command\sub\faction;

use CortexPE\Commando\BaseSubCommand;
use NCore\Base;
use NCore\handler\Cache;
use NCore\handler\FactionAPI;
use NCore\handler\RankAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class Delete extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "delete", "Supprimer sa faction", ["disband", "del"]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            $permission = FactionAPI::hasPermission($sender, $this->getName());

            if (is_null($permission)) {
                $sender->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction");
                return;
            } else if (!$permission) {
                $sender->sendMessage(Util::PREFIX . "Vous ne possèdez pas les permissions necessaire dans votre faction pour faire cela");
                return;
            }

            $faction = $session->data["player"]["faction"];
            FactionAPI::broadcastMessage($faction, "§e[§fF§r§e] §fLa faction dont vous êtiez n'existe désormais plus");

            foreach (FactionAPI::getFactionMembers($faction, true) as $player) {
                $session->data["player"]["faction"] = null;
                $session->data["player"]["faction_chat"] = false;
                RankAPI::updateNameTag($player);
            }

            if (!is_null(Cache::$factions[$faction]["claim"])) {
                $plot = Cache::$factions[$faction]["claim"];
                Cache::$plots[$plot]["faction"] = null;
            }

            unset(Cache::$factions[$faction]);
            FactionAPI::deleteIsland($faction);
        }
    }

    protected function prepare(): void
    {
    }
}