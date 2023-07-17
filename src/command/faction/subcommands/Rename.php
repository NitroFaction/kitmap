<?php

namespace Kitmap\command\faction\subcommands;

use CortexPE\Commando\args\RawStringArgument;
use Kitmap\command\faction\FactionCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\Faction;
use Kitmap\handler\Rank;
use Kitmap\handler\ScoreFactory;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\task\repeat\OutpostTask;
use Kitmap\Util;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Rename extends FactionCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "rename",
            "Renomme sa faction"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args): void
    {
        $name = strtolower($args["nom"]);

        if (Faction::exists($name)) {
            $sender->sendMessage(Util::PREFIX . "Ce nom de faction existe déjà");
            return;
        } else if (!ctype_alnum($name) || strlen($name) > 16) {
            $sender->sendMessage(Util::PREFIX . "Ce nom de faction est invalide ou trop long");
            return;
        }

        $data = Cache::$factions[$faction];
        $data["upper_name"] = $args["nom"];

        Cache::$factions[$name] = $data;

        foreach (Faction::getFactionMembers($faction, false) as $player) {
            $target = Main::getInstance()->getServer()->getPlayerExact($player);

            if ($target instanceof Player) {
                Session::get($target)->data["faction"] = $name;

                Rank::updateNameTag($target);
                ScoreFactory::updateScoreboard($target);
            } else {
                $username = strtolower($player);
                $file = Util::getFile("data/players/" . $username);

                if ($file->getAll() !== []) {
                    $file->set("faction", $name);
                    $file->save();
                }
            }
        }

        if (!is_null(Cache::$factions[$faction]["claim"])) {
            $claim = Cache::$factions[$faction]["claim"];
            Cache::$claims[$claim] = $name;
        }

        if (Cache::$data["outpost"] === $faction) {
            Cache::$data["outpost"] = $name;
        }
        if (OutpostTask::$currentFaction === $faction) {
            OutpostTask::$currentFaction = $name;
        }

        Faction::renameWorld("box-" . $faction, "box-" . $name);

        unset(Cache::$factions[$faction]);
        Cache::$factions[$name]["logs"][time()] = "§e" . $sender->getName() . " §frenome la faction §e" . $name;

        $sender->sendMessage(Util::PREFIX . "Vous venez de renommer votre faction §e" . $faction . " §fen §e" . $name);
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("nom"));
    }
}