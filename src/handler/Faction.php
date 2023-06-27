<?php

namespace Kitmap\handler;

use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\block\Barrel;
use pocketmine\block\Block;
use pocketmine\block\Chest;
use pocketmine\block\Door;
use pocketmine\block\FenceGate;
use pocketmine\block\Trapdoor;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\world\format\Chunk;

class Faction
{
    public static function getNextRank(string $rank): string
    {
        $ranks = array_keys(Cache::$config["faction_ranks"]);
        return $ranks[self::getRankPosition($rank) - 1] ?? $rank;
    }

    public static function getRankPosition(string $rank): int
    {
        $ranks = array_keys(Cache::$config["faction_ranks"]);
        return array_search($rank, $ranks);
    }

    public static function getPreviousRank(string $rank): string
    {
        $ranks = array_keys(Cache::$config["faction_ranks"]);
        return $ranks[self::getRankPosition($rank) + 1] ?? $rank;
    }

    public static function broadcastMessage(string $key, string $message): void
    {
        $members = self::getFactionMembers($key, true);

        foreach ($members as $player) {
            if ($player instanceof Player) {
                $player->sendMessage($message);
            }
        }
    }

    public static function getFactionMembers(string $key, bool $online): array
    {
        $arr = [];

        if (isset(Cache::$factions[$key])) {
            $list = Cache::$factions[$key]["members"];
            $leader = $list["leader"];

            if ($online) {
                /** @noinspection PhpDeprecationInspection */
                $leader = Main::getInstance()->getServer()->getPlayerByPrefix($leader);

                if ($leader instanceof Player) {
                    $arr[] = $leader;
                }
            } else {
                $arr[] = $leader;
            }
            $members = array_merge($list["officiers"], $list["members"], $list["recruits"]);

            foreach ($members as $player) {
                if ($online) {
                    /** @noinspection PhpDeprecationInspection */
                    $player = Main::getInstance()->getServer()->getPlayerByPrefix($player);

                    if ($player instanceof Player) {
                        $arr[] = $player;
                    }
                } else {
                    $arr[] = $player;
                }
            }
        }
        return $arr;
    }

    public static function getFactionUpperName(string $faction): string
    {
        return !isset(Cache::$factions[$faction]) ? $faction : Cache::$factions[$faction]["upper_name"];
    }

    public static function addPower(string $faction, int $amount): void
    {
        self::setPower($faction, $amount + self::getPower($faction));

        if (self::getPower($faction) < 0) {
            self::setPower($faction, 0);
        }
    }

    private static function setPower(string $faction, int $amount): void
    {
        Cache::$factions[$faction]["power"] = $amount;
    }

    public static function getPower(string $faction): int
    {
        return Cache::$factions[$faction]["power"];
    }

    public static function canBuild(Player $player, Block $block, string $type): bool
    {
        $session = Session::get($player);
        $faction = $session->data["faction"];

        if ($type === "break" && $session->inCooldown("_antibuild")) {
            $player->sendTip(Util::PREFIX . "Veuillez attendre §e" . ($session->getCooldownData("_antibuild")[0] - time()) . " §fseconde(s) avant de construire");
            return false;
        } elseif ($player->getGamemode() === GameMode::CREATIVE() && $player->hasPermission("pocketmine.group.operator")) {
            return true;
        } else {
            $claim = Faction::inClaim($block->getPosition()->getX(), $block->getPosition()->getZ());

            if ($claim[0]) {
                if ($type === "interact") {
                    $type = match (true) {
                        $block instanceof Door => "door",
                        $block instanceof Trapdoor => "trapdoor",
                        $block instanceof FenceGate => "fence-gates",
                        $block instanceof Chest, $block instanceof Barrel => "chest",
                        default => null
                    };
                } elseif ($type === "break" && ($block instanceof Chest || $block instanceof Barrel)) {
                    $type = "chest";
                }

                $permission = is_null($type) ? true : Faction::hasPermission($player, $type);

                if (is_bool($permission)) {
                    if ($type === "interact" || $permission) {
                        return $claim[1] == $faction;
                    }
                }
            }
            return false;
        }
    }

    public static function hasPermission(Player $player, string $permission): ?bool
    {
        $session = Session::get($player);
        $rank = self::getFactionRank($player);

        if (is_null($rank)) {
            return null;
        }

        $faction = $session->data["faction"];
        $data = Cache::$factions[$faction];

        if ($rank !== "leader") {
            $require = $data["permissions"][$permission];
            $passed = false;

            if ($rank === $require) {
                return true;
            }

            foreach (array_keys(Cache::$config["faction_ranks"]) as $value) {
                if (!$passed && $value === $require) {
                    return false;
                } elseif ($rank === $value) {
                    $passed = true;
                }
            }
        }
        return true;
    }

    public static function getFactionRank(string|Player $key, string $value = null): ?string
    {
        $session = null;

        if ($key instanceof Player) {
            $session = Session::get($key);
            $value = $key->getName();

            $key = $session->data["faction"];
        }

        if (!self::exists($key)) {
            if ($session instanceof Session) {
                $session->data["faction"] = null;
                $session->data["faction_chat"] = false;
            }
            return null;
        }

        $members = Cache::$factions[$key]["members"];

        if ($members["leader"] === $value) {
            return "leader";
        } elseif (in_array($value, $members["officiers"])) {
            return "officier";
        } elseif (in_array($value, $members["members"])) {
            return "member";
        } elseif (in_array($value, $members["recruits"])) {
            return "recruit";
        } else {
            if ($session instanceof Session) {
                $session->data["faction"] = null;
                $session->data["faction_chat"] = false;
            }
            return null;
        }
    }

    public static function exists(?string $key): bool
    {
        return isset(Cache::$factions[strtolower($key)]);
    }

    public static function inClaim(float $x, float $z): array
    {
        $chunkX = $x >> Chunk::COORD_BIT_SIZE;
        $chunkZ = $z >> Chunk::COORD_BIT_SIZE;

        $chunk = $chunkX . ":" . $chunkZ;

        if (isset(Cache::$claims[$chunk])) {
            return [true, Cache::$claims[$chunk], $chunk];
        } else {
            return [false, null, $chunk];
        }
    }

    public static function hasFaction(Player $player): bool
    {
        self::getFactionRank($player);
        return !is_null(Session::get($player)->data["faction"]);
    }
}