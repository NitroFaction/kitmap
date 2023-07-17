<?php

namespace NCore\command\sub\faction\admin;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use NCore\Base;
use NCore\handler\Cache;
use NCore\handler\FactionAPI;
use NCore\handler\RankAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;

class Delete extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "delete", "Supprime une faction", ["disband", "del"]);
        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $faction = strtolower($args["faction"]);

        if (!FactionAPI::exist($faction)) {
            $sender->sendMessage(Util::PREFIX . "La faction §e" . $faction . " §fn'existe pas");
            return;
        }

        $sender->sendMessage(Util::PREFIX . "Vous venez de supprimer la faction §e" . $faction);
        FactionAPI::broadcastMessage($faction, "§e[§fF§r§e] §fLa faction dont vous êtiez n'existe désormais plus");

        foreach (FactionAPI::getFactionMembers($faction, true) as $player) {
            $session = Session::get($player);

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

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("faction"));
    }
}