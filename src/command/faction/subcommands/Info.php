<?php

namespace Kitmap\command\faction\subcommands;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TargetArgument;
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
                $sender->sendMessage(Util::PREFIX . "Le joueur §e" . $player->getName() . " §fn'est dans aucune faction");
                return;
            }

            $this->sendInfo($faction, $sender);
            return;
        } else if (is_null($search)) {
            $sender->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction");
            return;
        } else if (!Faction::exists($search)) {
            $sender->sendMessage(Util::PREFIX . "La faction §e" . $search . " §fn'existe pas");
            return;
        }

        $this->sendInfo($search, $sender);
    }

    private function sendInfo(string $faction, CommandSender $player): void
    {
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
            $leader = "§e" . $_leader->getName() . "§7[§e" . Session::get($_leader)->data["kill"] . "§7]";
        } else {
            $leader = "§7" . $leader;
        }

        $power = Cache::$factions[$faction]["power"];
        $money = Cache::$factions[$faction]["money"];

        $connected = count(Faction::getFactionMembers($faction, true));
        $everyone = count(Faction::getFactionMembers($faction, false));

        $home = explode(":", Cache::$factions[$faction]["home"]);
        $home = (($home[0] ?? 0) == 0 && ($home[1] ?? 0) == 0 && ($home[2] ?? 0) == 0) ? "Aucun Home" : "X: §e" . $home[0] . "§f, Z: §e" . $home[2];

        $player->sendMessage($bar);
        $player->sendMessage("§e" . Faction::getFactionUpperName($faction) . " §f[§e" . $connected . "§f/§e" . $everyone . "§f] - " . $home);
        $player->sendMessage("§eChef§f: " . $leader);
        $player->sendMessage("§eOfficiers§f: " . $officiers);
        $player->sendMessage("§eMembres§f: " . $members);
        $player->sendMessage("§eRecrues§f: " . $recruits);
        $player->sendMessage("§eMoney§f: " . $money);
        $player->sendMessage("§ePowers§f: " . $power);
        $player->sendMessage($bar);
    }

    private function getMembersFormat(string $faction, string $rank): string
    {
        $arr = [];

        foreach (Cache::$factions[$faction]["members"][$rank] as $member) {
            $player = Main::getInstance()->getServer()->getPlayerExact($member);

            if ($player instanceof Player) {
                $arr[] = "§e" . $player->getName();
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