<?php

namespace Kitmap\task\repeat\event;

use Kitmap\handler\Cache;
use Kitmap\handler\Faction;
use Kitmap\handler\OtherAPI;
use Kitmap\handler\Rank;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;

class OutpostTask
{
    public static int $nextReward = 300;
    public static ?int $currentOutpost = 180;

    public static ?string $currentFaction = null;
    private static int $spam = 0;

    public static function run(): void
    {
        if (!isset(Cache::$data["outpost"])) {
            Cache::$data["outpost"] = null;
        }

        self::$nextReward--;
        $players = Main::getInstance()->getServer()->getOnlinePlayers();

        if (!is_null(Cache::$data["outpost"])) {
            if (self::$currentOutpost > 60) {
                self::$currentOutpost = 60;
            }

            if (!Faction::exists(Cache::$data["outpost"])) {
                Cache::$data["outpost"] = null;
                self::$currentOutpost = 180;
                return;
            } elseif (is_null(self::$currentFaction)) {
                foreach ($players as $player) {
                    $session = Session::get($player);

                    if (!$session->data["staff_mod"][0] && $player->isAlive() && OtherAPI::insideZone($player->getPosition(), "outpost") && Faction::hasFaction($player) && $session->data["faction"] !== Cache::$data["outpost"]) {
                        self::$currentFaction = $session->data["faction"];

                        if ((time() - self::$spam) > 5) {
                            Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "La faction §e" . Faction::getFactionUpperName(self::$currentFaction) . " §fest entrain de capturer l'outpost de la §e" . Faction::getFactionUpperName(Cache::$data["outpost"]) . " §f!");
                            self::$spam = time();
                        }
                        return;
                    }
                }

                self::$currentOutpost = 60;
            } else {
                if (!Faction::exists(self::$currentFaction) || !self::searchPlayersFaction(self::$currentFaction)) {
                    self::$currentFaction = null;
                    self::$currentOutpost = 60;
                    return;
                }
            }
            self::$currentOutpost--;

            if (0 >= self::$currentOutpost) {
                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "La faction §e" . Faction::getFactionUpperName(Cache::$data["outpost"]) . " §fne possède plus l'outpost !");

                Cache::$data["outpost"] = null;
                self::$currentOutpost = 180;
            }

            if (0 >= self::$nextReward && !is_null(Cache::$data["outpost"])) {
                self::$nextReward = 300;
                $members = Faction::getFactionMembers(Cache::$data["outpost"], true);

                foreach ($members as $player) {
                    $session = Session::get($player);

                    $rank = Rank::getEqualRank($player->getName());
                    $price = Rank::getRankValue($rank, "outpost");

                    $session->addValue("money", $price);
                    $player->sendMessage(Util::PREFIX . "Vous venez de recevoir §e" . $price . " §fgrace à l'outpost");
                }

                Faction::addPower(Cache::$data["outpost"], 8);
                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "La faction §e" . Faction::getFactionUpperName(Cache::$data["outpost"]) . " §fvient de récuperer leurs récompenses grace à l'outpost");
            }
            return;
        }

        if (is_null(self::$currentFaction)) {
            foreach ($players as $player) {
                $session = Session::get($player);

                if (!$session->data["staff_mod"][0] && $player->isAlive() && OtherAPI::insideZone($player->getPosition(), "outpost") && Faction::hasFaction($player)) {
                    self::$currentFaction = $session->data["faction"];

                    if ((time() - self::$spam) > 5) {
                        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "La faction §e" . Faction::getFactionUpperName(self::$currentFaction) . " §fest entrain de capturer l'outpost §f!");
                        self::$spam = time();
                    }
                    return;
                }
            }

            self::$currentOutpost = 180;
        } else {
            if (!Faction::exists(self::$currentFaction) || !self::searchPlayersFaction(self::$currentFaction)) {
                self::$currentFaction = null;
                self::$currentOutpost = 180;
                return;
            }
        }
        self::$currentOutpost--;

        if (0 >= self::$currentOutpost) {
            Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "La faction §e" . Faction::getFactionUpperName(self::$currentFaction) . " §fvient de capturer l'outpost");
            Cache::$data["outpost"] = self::$currentFaction;

            self::$currentFaction = null;
            self::$currentOutpost = 60;
            self::$nextReward = 300;
        }
    }

    private static function searchPlayersFaction(string $faction): bool
    {
        $found = false;

        foreach (Faction::getFactionMembers($faction, true) as $player) {
            if (!Session::get($player)->data["staff_mod"][0] && $player->isAlive() && OtherAPI::insideZone($player->getPosition(), "outpost")) {
                $found = true;
            }
        }
        return $found;
    }
}