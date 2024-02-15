<?php

namespace Kitmap\task\repeat\child;

use Kitmap\handler\Cache;
use Kitmap\handler\Faction;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\Position;

class DominationTask
{
    public static bool $currentDomination = false;

    public static array $factions = [];
    public static array $zones = [];

    public static int $time = 900;

    public static function getScoreboardLines(): array
    {
        $lines = [
            "§f ",
            "§fTemps restant: §9" . Util::formatDurationFromSeconds(DominationTask::$time, 1),
            "§7 ",
        ];

        $i = 0;

        foreach (Cache::$config["domination"] as $key => $value) {
            $i++;
            $status = self::$zones[$key][1][0] ?? "uncaptured";

            $status = match ($status) {
                "captured" => "§aCapturé",
                "uncaptured" => "§7Libre",
                "contested" => "§cContesté"
            };

            $actual = self::$zones[$key][0] ?? null;
            $actual = is_null($actual) ? "Aucune Faction" : Faction::getFactionUpperName($actual);

            if ($status === "§7Libre") {
                $actual = "Aucune Faction";
            }

            $lines[] = "§f" . $key . " §9(" . $status . "§9)";
            $lines[] = "  §r§f§l| §r§9" . $actual . "§" . $i;
        }

        $lines[] = "§8 ";

        $leaderboard = self::$factions;
        arsort($leaderboard);

        $i = 0;

        foreach ($leaderboard as $key => $value) {
            $i++;
            $lines[] = "§f" . $i . ". §9" . Faction::getFactionUpperName($key) . "§f: " . $value;

            if ($i >= 3) {
                break;
            }
        }

        $lines[] = "§9 ";
        $lines[] = "     §7nitrofaction.fr    ";

        return $lines;
    }

    public static function updateZoneBlocks(string $zone, string $status = null): void
    {
        [$minX, $maxX, $minZ, $maxZ, $y] = array_values(Cache::$config["domination"][$zone]);

        $block = match ($status) {
            "captured" => VanillaBlocks::CONCRETE_POWDER()->setColor(DyeColor::GREEN()),
            "contested" => VanillaBlocks::CONCRETE_POWDER()->setColor(DyeColor::RED()),
            default => VanillaBlocks::CONCRETE_POWDER()->setColor(DyeColor::LIGHT_GRAY())
        };

        for ($x = $minX; $x <= $maxX; $x++) {
            for ($z = $minZ; $z <= $maxZ; $z++) {
                Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->setBlock(new Vector3($x, $y, $z), $block);
            }
        }
    }

    public static function run(): void
    {
        if (!self::$currentDomination) {
            return;
        }

        self::$time--;
        $players = Main::getInstance()->getServer()->getOnlinePlayers();

        foreach (Cache::$config["domination"] as $key => $value) {
            $actual = self::$zones[$key] ?? [null, ["uncaptured", time(), []]];

            $insides = [];

            foreach ($players as $player) {
                if (self::playerInsideZone($key, $player) && Faction::hasFaction($player)) {
                    $session = Session::get($player);
                    $insides[] = $session->data["faction"];
                }
            }

            switch ($actual[1][0]) {
                case "uncaptured":
                    if (is_null($actual[0])) {
                        $faction = array_shift($insides);

                        if (is_null($faction)) {
                            break;
                        }

                        $actual[0] = $faction;
                        $actual[1][1] = time();

                        Main::getInstance()->getServer()->broadcastTip(Util::PREFIX . "La faction §9" . Faction::getFactionUpperName($faction) . " §fest entrain de capturer la zone §9" . $key);
                    } else {
                        $actual_faction = $actual[0];

                        if (!in_array($actual_faction, $insides)) {
                            $actual[0] = null;
                            $actual[1][1] = time();
                        } else if (time() - $actual[1][1] >= 5) {
                            Main::getInstance()->getServer()->broadcastTip(Util::PREFIX . "La faction §9" . Faction::getFactionUpperName($actual_faction) . " §fa capturé la zone §9" . $key);

                            $actual[0] = $actual_faction;
                            $actual[1][0] = "captured";
                            $actual[1][1] = time();
                        }
                    }
                    break;
                case "contested":
                    $contest_faction = $actual[1][2][0];

                    if (!in_array($contest_faction, $insides)) {
                        $actual[1][0] = "captured";
                        $actual[1][1] = time();
                        $actual[1][2] = [];
                    } else if (time() - $actual[1][1] >= 5) {
                        Main::getInstance()->getServer()->broadcastTip(Util::PREFIX . "La faction §9" . Faction::getFactionUpperName($contest_faction) . " §fa contesté la zone §9" . $key . " §fappartenant à la faction §9" . Faction::getFactionUpperName($actual[0]));

                        $actual[0] = null;
                        $actual[1][0] = "uncaptured";
                        $actual[1][1] = time();
                        $actual[1][2] = [];
                        break;
                    }

                    $points = self::$factions[$actual[0]] ?? 0;
                    self::$factions[$actual[0]] = $points + 1;
                    break;
                case "captured":
                    shuffle($insides);

                    $actual_faction = $actual[0];
                    $insides = array_filter($insides, fn($value) => $value !== $actual_faction);

                    $contest_faction = $insides[0] ?? null;

                    $points = self::$factions[$actual_faction] ?? 0;
                    self::$factions[$actual_faction] = $points + 1;

                    if (!is_null($contest_faction)) {
                        $actual[1][0] = "contested";
                        $actual[1][1] = time();
                        $actual[1][2] = [$contest_faction];

                        Main::getInstance()->getServer()->broadcastTip(Util::PREFIX . "La faction §9" . Faction::getFactionUpperName($contest_faction) . " §fconteste la zone §9" . $key . " §fappartenant à la faction §9" . Faction::getFactionUpperName($actual[0]));
                    }
                    break;
            }

            self::$zones[$key] = $actual;
        }

        if (0 >= self::$time) {
            $leaderboard = self::$factions;
            arsort($leaderboard);

            $leaderboard = array_slice(array_keys($leaderboard), 0, 3);
            $factions = implode("§f, §9", $leaderboard);

            Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "L'event §9domination §fest terminé ! Voici les factions gagnantes: §9" . $factions);
            $i = 0;

            foreach ($leaderboard as $key) {
                $i++;
                $power = 200 - (($i - 1) * 50);

                Faction::addPower($key, $power);
                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "La faction §9 " . Faction::getFactionUpperName($key) . " §fqui est arrivé §9" . $i . " §fà l'event §9domination §fa remporté §9" . $power . " §fpowers");
            }

            Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "À bientôt pour un prochain event !");

            self::$currentDomination = false;
            self::$factions = [];
            self::$zones = [];
            self::$time = 900;
        }
    }

    private static function playerInsideZone(string $zone, Player $player): bool
    {
        $session = Session::get($player);

        if ($session->data["staff_mod"][0] || !$player->isAlive() || $player->getWorld() !== Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()) {
            return false;
        }

        return self::insideZone($zone, $player->getPosition());
    }

    public static function insideZone(string $zone, Position $position): bool
    {
        $value = Cache::$config["domination"][$zone];

        $x = $position->getFloorX();
        $y = $position->getFloorY();
        $z = $position->getFloorZ();

        return $x >= $value["minX"] && $x <= $value["maxX"] && $y >= $value["y"] - 3 && $y <= $value["y"] + 3 && $z >= $value["minZ"] && $z <= $value["maxZ"];
    }
}