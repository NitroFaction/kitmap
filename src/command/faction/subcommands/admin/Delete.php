<?php

namespace Kitmap\command\faction\subcommands\admin;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\Faction;
use Kitmap\handler\Rank;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;

class Delete extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Main::getInstance(), "delete", "Supprime une faction", ["disband", "del"]);
        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $faction = strtolower($args["faction"]);

        if (!Faction::exists($faction)) {
            $sender->sendMessage(Util::PREFIX . "La faction §q" . $faction . " §fn'existe pas");
            return;
        }

        $sender->sendMessage(Util::PREFIX . "Vous venez de supprimer la faction §q" . $faction);
        Faction::broadcastMessage($faction, "§q[§fF§r§q] §fLa faction dont vous êtiez n'existe désormais plus");

        foreach (Faction::getFactionMembers($faction, true) as $player) {
            $session = Session::get($player);

            $session->data["faction"] = null;
            $session->data["faction_chat"] = false;

            Rank::updateNameTag($player);
        }

        if (!is_null(Cache::$factions[$faction]["claim"])) {
            $claim = Cache::$factions[$faction]["claim"];
            unset(Cache::$claims[$claim]);
        }

        unset(Cache::$factions[$faction]);
        Faction::deleteIsland($faction);
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("faction"));
    }
}