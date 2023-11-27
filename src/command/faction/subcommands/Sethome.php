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

class Sethome extends FactionCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "sethome",
            "Défini un point de téléportation commun à une faction"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args): void
    {
        if (Faction::inClaim($sender->getPosition()->getFloorX(), $sender->getPosition()->getFloorZ())[1] !== $faction) {
            $sender->sendMessage(Util::PREFIX . "Vous devez être dans votre claim pour faire cela");
            return;
        }

        Cache::$factions[$faction]["home"] = round($sender->getPosition()->x) . ":" . round($sender->getPosition()->y) . ":" . round($sender->getPosition()->z);
        Cache::$factions[$faction]["logs"][time()] = "§q" . $sender->getName() . " §fsethome (§q" . Cache::$factions[$faction]["home"] . "§f)";

        $sender->sendMessage(Util::PREFIX . "Vous venez de définir le point de téléportation de votre home");
    }

    protected function prepare(): void
    {
    }
}