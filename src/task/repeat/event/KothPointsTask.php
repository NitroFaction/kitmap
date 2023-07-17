<?php

namespace NCore\task\repeat\event;

use NCore\Base;
use NCore\entity\entities\MessageEntity;
use NCore\handler\OtherAPI;
use NCore\handler\SanctionAPI;
use NCore\handler\ScoreFactory;
use NCore\Session;
use NCore\Util;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;

class KothPointsTask
{
    public static ?int $currentKothPoints = null;
    public static array $points = [];

    public static function run(): void
    {
        $players = Base::getInstance()->getServer()->getOnlinePlayers();

        if (!is_numeric(self::$currentKothPoints)) {
            return;
        }

        foreach ($players as $player) {
            if (!Session::get($player)->data["player"]["staff_mod"][0] && $player->isAlive() && OtherAPI::insideZone($player->getPosition(), "koth_points")) {
                if (!isset(self::$points[$player->getName()])) {
                    self::$points[$player->getName()] = 0;
                }

                self::$points[$player->getName()]++;

                $player->sendTip(Util::PREFIX . "§e+1 §fpoint §7(§e" . self::$points[$player->getName()] . "§7)");

                $location = new Location($player->getLocation()->getX(), $player->getLocation()->getY() + 2, $player->getLocation()->getZ(), $player->getLocation()->getWorld(), 0, 0);

                $nbt = CompoundTag::create()->setString("message", "§e+ 1");
                $entity = new MessageEntity($location, $nbt);

                $entity->spawnToAll();
                $entity->setMotion(new Vector3(0.001, 0.05, 0.001));
            }
        }

        self::$currentKothPoints--;

        if (0 >= self::$currentKothPoints) {
            foreach ($players as $player) {
                ScoreFactory::updateScoreboard($player);
            }

            $players = [];

            $leaderboard = self::$points;
            asort($leaderboard);

            for ($i = 0; $i < 3; $i++) {
                $player = Base::getInstance()->getServer()->getPlayerByPrefix($leaderboard[$i] ?? "Personne");

                if ($player instanceof Player) {
                    $players[] = $player->getName();
                    $session = Session::get($player);

                    $session->addValue("money", 5000);
                    $session->addValue("pack", 1);
                }
            }

            Base::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§e" . implode("§f, §e", $players) . " §font gagné §e15 000$ §fet §e3 packs §fcar ils ont remporté l'event koth points !");

            self::$currentKothPoints = null;
            self::$points = [];
        }
    }

    public static function getScoreboardLines(): array
    {
        $lines = [
            "§f ",
            Util::PREFIX . "Koth Points",
            "§fTemps restant: §e" . SanctionAPI::format(self::$currentKothPoints, 1),
            "§7 ",
        ];

        $leaderboard = self::$points;
        arsort($leaderboard);

        for ($i = 0; $i < 3; $i++) {
            $key = array_keys($leaderboard)[$i] ?? "Personne";
            $value = $leaderboard[$key] ?? 0;

            $lines[] = "§f" . ($i + 1) . ". §e" . $key . "§f: " . $value;
        }

        $lines[] = "§8 ";
        $lines[] = "     §7nitrofaction.fr    ";

        return $lines;
    }
}