<?php

namespace Kitmap\command\faction\subcommands;

use Kitmap\command\faction\FactionCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\Faction;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Leave extends FactionCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "leave",
            "Quitte sa faction actuel"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
        $this->setAliases(["quit"]);
    }

    public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args): void
    {
        $rank = Faction::getFactionRank($faction, $sender->getName());

        if ($rank === "leader") {
            $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas quitter votre faction si vous en êtes chef");
            return;
        }

        unset(Cache::$factions[$faction]["members"][$rank . "s"][array_search($sender->getName(), Cache::$factions[$faction]["members"][$rank . "s"])]);

        $session->data["faction"] = null;
        $session->data["faction_chat"] = false;

        Cache::$factions[$faction]["logs"][time()] = "§e" . $sender->getName() . " §fquitte la faction";

        $sender->sendMessage(Util::PREFIX . "Vous venez de quitter votre faction");
        Faction::broadcastMessage($faction, "§e[§fF§e] §fLe joueur §e" . $sender->getName() . " §fvient de quitter votre faction");
    }

    protected function prepare(): void
    {
    }
}