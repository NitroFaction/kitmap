<?php

namespace Kitmap\command\faction\subcommands;

use CortexPE\Commando\args\RawStringArgument;
use Kitmap\command\faction\FactionCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\Faction;
use Kitmap\handler\Rank;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Create extends FactionCommand
{
    protected bool $requiresFaction = false;

    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "create",
            "Créer sa faction"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args): void
    {
        $name = strtolower($args["nom"]);

        if (!is_null($faction)) {
            $sender->sendMessage(Util::PREFIX . "Vous appartenez déjà à une faction");
            return;
        } elseif (Faction::exists($name)) {
            $sender->sendMessage(Util::PREFIX . "Ce nom de faction existe déjà");
            return;
        } elseif (!ctype_alnum($name) || strlen($name) > 16) {
            $sender->sendMessage(Util::PREFIX . "Ce nom de faction est invalide ou trop long");
            return;
        }

        Cache::$factions[$name] = [
            "upper_name" => $args["nom"],
            "home" => "0:0:0",
            "claim" => null,
            "members" => [
                "leader" => $sender->getName(),
                "officiers" => [],
                "members" => [],
                "recruits" => []
            ],
            "permissions" => [
                "delete" => "leader",
                "leader" => "leader",
                "permissions" => "leader",

                "invite" => "officier",
                "kick" => "officier",
                "sethome" => "officier",
                "delhome" => "officier",
                "claim" => "officier",
                "unclaim" => "officier",
                "logs" => "officier",
                "rename" => "officier",
                "demote" => "officier",
                "promote" => "officier",

                "place" => "member",
                "break" => "member",
                "chest" => "member",

                "fence-gates" => "recruit",
                "trapdoor" => "recruit",
                "door" => "recruit",
                "home" => "recruit"
            ],
            "activity" => [],
            "power" => 0,
            "money" => 0
        ];

        $session->data["faction"] = $name;

        $sender->sendMessage(Util::PREFIX . "Vous venez de créer votre faction §e" . $args["nom"] . " §f!");
        Rank::updateNameTag($sender);
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("nom"));
    }
}