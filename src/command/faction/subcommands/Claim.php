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

class Claim extends FactionCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "claim",
            "Claim une zone spécifique"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args): void
    {
        if (!is_null(Cache::$factions[$faction]["claim"])) {
            $sender->sendMessage(Util::PREFIX . "Vous avez déjà un claim. Faites §q/f unclaim§f pour le supprimer");
            return;
        } else if (!Faction::canClaim($sender->getPosition())) {
            $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas claim ici");
            return;
        }

        $claim = Faction::inClaim($sender->getPosition()->getFloorX(), $sender->getPosition()->getFloorZ());

        if ($claim[0]) {
            $sender->sendMessage(Util::PREFIX . "Ce claim n'est pas libre");
            return;
        }

        Cache::$factions[$faction]["claim"] = $claim[2];
        Cache::$claims[$claim[2]] = $faction;

        Cache::$factions[$faction]["logs"][time()] = "§q" . $sender->getName() . " §fa récupéré un nouveau claim";
        Faction::broadcastMessage($faction, "§q[§fF§r§q] §fVotre faction vient de récuperer un nouveau claim");
    }

    protected function prepare(): void
    {
    }
}