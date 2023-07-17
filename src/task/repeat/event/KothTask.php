<?php

namespace NCore\task\repeat\event;

use NCore\Base;
use NCore\handler\FactionAPI;
use NCore\handler\OtherAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\player\Player;
use skymin\bossbar\BossBarAPI;

class KothTask
{
    public static ?int $currentKoth = null;
    public static ?string $currentPlayer = null;

    public static function run(): void
    {
        $players = Base::getInstance()->getServer()->getOnlinePlayers();

        if (!is_numeric(self::$currentKoth)) {
            return;
        }

        foreach ($players as $player) {
            $playerName = self::$currentPlayer;
            $playerName = is_null($playerName) ? "Aucun Joueur" : $playerName;

            $percentage = ((KothTask::$currentKoth + 1) / 180);

            BossBarAPI::getInstance()->sendBossBar(
                $player,
                "Koth | Controlé par " . $playerName,
                1,
                $percentage
            );
        }

        if (is_null(self::$currentPlayer)) {
            foreach ($players as $player) {
                if (!Session::get($player)->data["player"]["staff_mod"][0] && $player->isAlive() && OtherAPI::insideZone($player->getPosition(), "koth")) {
                    self::$currentPlayer = $player->getName();
                    return;
                }
            }

            self::$currentKoth = 180;
        } else {
            $player = Base::getInstance()->getServer()->getPlayerByPrefix(self::$currentPlayer);

            if (!$player instanceof Player || Session::get($player)->data["player"]["staff_mod"][0] || !$player->isAlive() || !OtherAPI::insideZone($player->getPosition(), "koth")) {
                self::$currentPlayer = null;
                self::$currentKoth = 180;
                return;
            }
        }

        self::$currentKoth--;

        if (0 >= self::$currentKoth) {
            $name = is_null(self::$currentPlayer) ? "aucun joueur" : self::$currentPlayer;
            Base::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "L'event §eKOTH §fvient de se terminer et a été gagné par §e" . $name);

            foreach ($players as $player) {
                BossBarAPI::getInstance()->hideBossBar($player, 1);
            }

            if (!is_null(self::$currentPlayer)) {
                $player = Base::getInstance()->getServer()->getPlayerByPrefix(self::$currentPlayer);

                if ($player instanceof Player) {
                    $session = Session::get($player);

                    $session->addValue("money", 25000);
                    $session->addValue("pack", 2);

                    if (FactionAPI::hasFaction($player)) {
                        FactionAPI::addPower($session->data["player"]["faction"], mt_rand(15, 25));
                    }

                    $player->sendMessage(Util::PREFIX . "Vous venez de recevoir §e2 pack §fet §e25k §fpièces car vous avez gagné l'event koth");
                }
            }

            self::$currentKoth = null;
            self::$currentPlayer = null;
        }
    }
}