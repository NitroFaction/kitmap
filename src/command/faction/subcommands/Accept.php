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

class Accept extends FactionCommand
{
    protected bool $requiresFaction = false;

    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "accept",
            "Accepter une invitation de la part d'une faction"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
        $this->setAliases(["join"]);
    }

    public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args): void
    {
        if (!is_null($faction)) {
            $sender->sendMessage(Util::PREFIX . "Vous appartenez déjà à une faction");
            return;
        }

        if (!isset($args["faction"])) {
            if (count($session->data["invite"]) === 0) {
                $sender->sendMessage(Util::PREFIX . "Vous ne possèdez aucune invitation pour rejoindre une faction");
                return;
            } else if (count($session->data["invite"]) != 1) {
                $sender->sendMessage(Util::PREFIX . "Vous possèdez plus qu'une invitation de faction, merci d'écrire la faction que vous voulez rejoindre");
                return;
            }

            $faction = $session->data["invite"][0];
            $this->join($faction, $sender);
        } else {
            $members = Faction::getFactionMembers(strtolower($args["faction"]), false);

            if (!in_array(strtolower($args["faction"]), $session->data["invite"])) {
                $sender->sendMessage(Util::PREFIX . "Vous n'avez aucune invitation provenant de cette faction");
                return;
            } else if (count($members) >= 20) {
                $sender->sendMessage(Util::PREFIX . "Cette faction ne peut pas comporter plus de 20 joueurs");
                return;
            }

            $this->join(strtolower($args["faction"]), $sender);
        }

        Rank::updateNameTag($sender);
    }

    private function join(string $faction, Player $player): void
    {
        $session = Session::get($player);

        if (!Faction::exists($faction)) {
            $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas accepter une invitation d'une faction inexistante");
            return;
        }

        Cache::$factions[$faction]["members"]["recruits"][] = $player->getName();
        Cache::$factions[$faction]["logs"][time()] = "§6" . $player->getName() . " §fa rejoint la faction";

        $session->data["faction"] = $faction;
        $session->data["invite"] = [];

        Faction::broadcastMessage($faction, "§6[§fF§6] §fLe joueur §6" . $player->getName() . " §fvient de rejoindre votre faction");
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("faction", true));
    }
}