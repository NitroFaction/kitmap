<?php

namespace Kitmap\command\faction\subcommands;

use CortexPE\Commando\args\RawStringArgument;
use Element\util\args\TargetArgument;
use Kitmap\command\faction\FactionCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\Faction;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Info extends FactionCommand
{
    protected bool $requiresFaction = false;

    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "info",
            "Récupére les informations à propos d'une faction ou de la sienne"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args): void
    {
        $search = $args["faction"] ?? $args["joueur"] ?? $faction;
        $player = Main::getInstance()->getServer()->getPlayerExact(strval($search));

        if ($player instanceof Player) {
            Faction::hasFaction($player);
            $faction = Session::get($player)->data["faction"];

            if ($faction === null) {
                $sender->sendMessage(Util::PREFIX . "Le joueur §q" . $player->getName() . " §fn'est dans aucune faction");
                return;
            }

            $this->sendInfo($faction, $sender);
            return;
        } else if (is_null($search)) {
            $sender->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction");
            return;
        } else if (!Faction::exists($search)) {
            $sender->sendMessage(Util::PREFIX . "La faction §q" . $search . " §fn'existe pas");
            return;
        }

        $this->sendInfo($search, $sender);
    }

    private function sendInfo(string $faction, CommandSender $player): void
    {
        $faction = strtolower($faction);

        if (!Faction::exists($faction)) {
            $player->sendMessage(Util::PREFIX . "La faction §q" . $faction . " §fn'existe pas");
            return;
        }

        $bar = "§l§8-----------------------";
        $leader = Cache::$factions[$faction]["members"]["leader"];

        $officiers = self::getMembersFormat($faction, "officiers");
        $members = self::getMembersFormat($faction, "members");
        $recruits = self::getMembersFormat($faction, "recruits");

        $officiers = $officiers === "" ? "Aucun officier" : $officiers;
        $members = $members === "" ? "Aucun membres" : $members;
        $recruits = $recruits === "" ? "Aucune recrues" : $recruits;

        $_leader = Main::getInstance()->getServer()->getPlayerExact($leader);

        if ($_leader instanceof Player) {
            $leader = "§q" . $_leader->getName();
        } else {
            $leader = "§7" . $leader;
        }

        $power = Cache::$factions[$faction]["power"];

        $connected = count(Faction::getFactionMembers($faction, true));
        $everyone = count(Faction::getFactionMembers($faction, false));

        $home = explode(":", Cache::$factions[$faction]["home"]);
        $home = (($home[0] ?? 0) == 0 && ($home[1] ?? 0) == 0 && ($home[2] ?? 0) == 0) ? "Aucun Home" : "X: §q" . $home[0] . "§f, Z: §q" . $home[2];

        $player->sendMessage($bar);
        $player->sendMessage("§q" . Faction::getFactionUpperName($faction) . " §f[§q" . $connected . "§f/§q" . $everyone . "§f] - " . $home);
        $player->sendMessage("§qChef§f: " . $leader);
        $player->sendMessage("§qOfficiers§f: " . $officiers);
        $player->sendMessage("§qMembres§f: " . $members);
        $player->sendMessage("§qRecrues§f: " . $recruits);
        $player->sendMessage("§qPowers§f: " . $power);
        $player->sendMessage($bar);
    }

    private function getMembersFormat(string $faction, string $rank): string
    {
        $arr = [];

        foreach (Cache::$factions[$faction]["members"][$rank] as $member) {
            $player = Main::getInstance()->getServer()->getPlayerExact($member);

            if ($player instanceof Player) {
                $arr[] = "§q" . $player->getName();
            } else {
                $arr[] = "§7" . $member;
            }
        }

        return implode("§7,", $arr);
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("faction", true));
        $this->registerArgument(0, new TargetArgument("joueur", true));
    }
}