<?php

namespace NCore\task\repeat\event;

use NCore\Base;
use NCore\handler\Cache;
use NCore\handler\FactionAPI;
use NCore\handler\OtherAPI;
use NCore\handler\RankAPI;
use NCore\Session;
use NCore\Util;

class OutpostTask
{
    public static int $nextReward = 300;
    public static ?int $currentOutpost = 180;

    public static ?string $currentFaction = null;
    private static int $spam = 0;

    public static function run(): void
    {
        if (!isset(Cache::$dynamic["outpost"])) {
            Cache::$dynamic["outpost"] = null;
        }

        self::$nextReward--;
        $players = Base::getInstance()->getServer()->getOnlinePlayers();

        if (!is_null(Cache::$dynamic["outpost"])) {
            if (self::$currentOutpost > 60) {
                self::$currentOutpost = 60;
            }

            if (!FactionAPI::exist(Cache::$dynamic["outpost"])) {
                Cache::$dynamic["outpost"] = null;
                self::$currentOutpost = 180;
                return;
            } else if (is_null(self::$currentFaction)) {
                foreach ($players as $player) {
                    $session = Session::get($player);

                    if (!$session->data["player"]["staff_mod"][0] && $player->isAlive() && OtherAPI::insideZone($player->getPosition(), "outpost") && FactionAPI::hasFaction($player) && $session->data["player"]["faction"] !== Cache::$dynamic["outpost"]) {
                        self::$currentFaction = $session->data["player"]["faction"];

                        if ((time() - self::$spam) > 5) {
                            Base::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "La faction §e" . FactionAPI::getFactionUpperName(self::$currentFaction) . " §fest entrain de capturer l'outpost de la §e" . FactionAPI::getFactionUpperName(Cache::$dynamic["outpost"]) . " §f!");
                            self::$spam = time();
                        }
                        return;
                    }
                }

                self::$currentOutpost = 60;
            } else {
                if (!FactionAPI::exist(self::$currentFaction) || !self::searchPlayersFaction(self::$currentFaction)) {
                    self::$currentFaction = null;
                    self::$currentOutpost = 60;
                    return;
                }
            }
            self::$currentOutpost--;

            if (0 >= self::$currentOutpost) {
                Base::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "La faction §e" . FactionAPI::getFactionUpperName(Cache::$dynamic["outpost"]) . " §fne possède plus l'outpost !");

                Cache::$dynamic["outpost"] = null;
                self::$currentOutpost = 180;
            }

            if (0 >= self::$nextReward && !is_null(Cache::$dynamic["outpost"])) {
                self::$nextReward = 300;
                $members = FactionAPI::getFactionMembers(Cache::$dynamic["outpost"], true);

                foreach ($members as $player) {
                    $session = Session::get($player);

                    $rank = RankAPI::getEqualRank($player->getName());
                    $price = RankAPI::getRankValue($rank, "outpost");

                    $session->addValue("money", $price);
                    $player->sendMessage(Util::PREFIX . "Vous venez de recevoir §e" . $price . " §fgrace à l'outpost");
                }

                FactionAPI::addPower(Cache::$dynamic["outpost"], 8);
                Base::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "La faction §e" . FactionAPI::getFactionUpperName(Cache::$dynamic["outpost"]) . " §fvient de récuperer leurs récompenses grace à l'outpost");
            }
            return;
        }

        if (is_null(self::$currentFaction)) {
            foreach ($players as $player) {
                $session = Session::get($player);

                if (!$session->data["player"]["staff_mod"][0] && $player->isAlive() && OtherAPI::insideZone($player->getPosition(), "outpost") && FactionAPI::hasFaction($player)) {
                    self::$currentFaction = $session->data["player"]["faction"];

                    if ((time() - self::$spam) > 5) {
                        Base::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "La faction §e" . FactionAPI::getFactionUpperName(self::$currentFaction) . " §fest entrain de capturer l'outpost §f!");
                        self::$spam = time();
                    }
                    return;
                }
            }

            self::$currentOutpost = 180;
        } else {
            if (!FactionAPI::exist(self::$currentFaction) || !self::searchPlayersFaction(self::$currentFaction)) {
                self::$currentFaction = null;
                self::$currentOutpost = 180;
                return;
            }
        }
        self::$currentOutpost--;

        if (0 >= self::$currentOutpost) {
            Base::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "La faction §e" . FactionAPI::getFactionUpperName(self::$currentFaction) . " §fvient de capturer l'outpost");
            Cache::$dynamic["outpost"] = self::$currentFaction;

            self::$currentFaction = null;
            self::$currentOutpost = 60;
            self::$nextReward = 300;
        }
    }

    private static function searchPlayersFaction(string $faction): bool
    {
        $found = false;

        foreach (FactionAPI::getFactionMembers($faction, true) as $player) {
            if (!Session::get($player)->data["player"]["staff_mod"][0] && $player->isAlive() && OtherAPI::insideZone($player->getPosition(), "outpost")) {
                $found = true;
            }
        }
        return $found;
    }
}