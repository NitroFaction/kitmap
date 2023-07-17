<?php

namespace NCore\command\sub\faction;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TargetArgument;
use CortexPE\Commando\BaseSubCommand;
use NCore\Base;
use NCore\handler\Cache;
use NCore\handler\FactionAPI;
use NCore\handler\OtherAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class Info extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "info", "Récuperer les informations de sa faction ou une autre");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (isset($args["faction"])) {
            $faction = strtolower($args["faction"]);

            if (FactionAPI::exist($faction)) {
                $this->sendInfo($faction, $sender);
            } else {
                $sender->sendMessage(Util::PREFIX . "La faction §e" . $args["faction"] . " §fn'existe pas");
            }
        } else if (isset($args["joueur"])) {
            $target = Base::getInstance()->getServer()->getPlayerByPrefix($args["joueur"]);

            if ($target instanceof Player) {
                if (FactionAPI::hasFaction($target)) {
                    $this->sendInfo(Session::get($target)->data["player"]["faction"], $sender);
                } else {
                    $sender->sendMessage(Util::PREFIX . "Ce joueur ne possède pas de faction");
                }
            } else {
                $sender->sendMessage(Util::PREFIX . "Le joueur §e" . $args["joueur"] . " §fn'existe pas ou n'est pas connecté");
            }
        } else {
            if ($sender instanceof Player) {
                if (FactionAPI::hasFaction($sender)) {
                    $this->sendInfo(Session::get($sender)->data["player"]["faction"], $sender);
                } else {
                    $sender->sendMessage(Util::PREFIX . "Vous ne possèdez pas de faction");
                }
            }
        }
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

        $_leader = Base::getInstance()->getServer()->getPlayerByPrefix($leader);

        if ($_leader instanceof Player) {
            $leader = "§e" . $_leader->getName() . "§7[§e" . Session::get($_leader)->data["player"]["kill"] . "§7]";
        } else {
            $leader = "§7" . $leader;
        }

        $power = OtherAPI::format(Cache::$factions[$faction]["power"]);
        $money = OtherAPI::format(Cache::$factions[$faction]["money"]);

        $connected = count(FactionAPI::getFactionMembers($faction, true));
        $everyone = count(FactionAPI::getFactionMembers($faction, false));

        $home = explode(":", Cache::$factions[$faction]["home"]);
        $home = (($home[0] ?? 0) == 0 && ($home[1] ?? 0) == 0 && ($home[2] ?? 0) == 0) ? "Aucun Home" : "X: §e" . $home[0] . "§f, Z: §e" . $home[2];

        $player->sendMessage($bar);
        $player->sendMessage("§e" . FactionAPI::getFactionUpperName($faction) . " §f[§e" . $connected . "§f/§e" . $everyone . "§f] - " . $home);
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
        $str = null;

        foreach (Cache::$factions[$faction]["members"][$rank] as $player) {
            $_player = Base::getInstance()->getServer()->getPlayerByPrefix($player);

            if ($_player instanceof Player) {
                if (is_null($str)) {
                    $str = "§e" . $_player->getName() . "§7[§e" . Session::get($_player)->data["player"]["kill"] . "§7]";
                } else {
                    $str = $str . "§f, §e" . $_player->getName() . "§7[§e" . Session::get($_player)->data["player"]["kill"] . "§7]";
                }
            } else {
                if (is_null($str)) {
                    $str = "§7" . $player;
                } else {
                    $str = $str . "§f, §7" . $player;
                }
            }
        }

        if (is_null($str)) {
            $str = "";
        }
        return $str;
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("faction", true));
        $this->registerArgument(0, new TargetArgument("joueur", true));
    }
}