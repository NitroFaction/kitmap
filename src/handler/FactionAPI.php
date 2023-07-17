<?php

namespace NCore\handler;

use NCore\Base;
use NCore\Session;
use NCore\Util;
use pocketmine\block\Barrel;
use pocketmine\block\Block;
use pocketmine\block\Chest;
use pocketmine\block\Door;
use pocketmine\block\FenceGate;
use pocketmine\block\Trapdoor;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\world\format\io\data\BaseNbtWorldData;
use pocketmine\world\World;
use Webmozart\PathUtil\Path;

class FactionAPI
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
                $leader = Base::getInstance()->getServer()->getPlayerByPrefix($leader);

                if ($leader instanceof Player) {
                    $arr[] = $leader;
                }
            } else {
                $arr[] = $leader;
            }
            $members = array_merge($list["officiers"], $list["members"], $list["recruits"]);

            foreach ($members as $player) {
                if ($online) {
                    $player = Base::getInstance()->getServer()->getPlayerByPrefix($player);

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

    public static function isIslandLocked(string $key): bool
    {
        return FactionAPI::exist($key) ? Cache::$factions[$key]["island"]["lock"] : false;
    }

    public static function exist(?string $key): bool
    {
        return isset(Cache::$factions[strtolower($key)]);
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

    public static function deleteIsland(string $key): void
    {
        $name = "island-" . $key;
        $newName = $name . "-deleted-" . time();

        $path = Path::join(Base::getInstance()->getServer()->getDataPath(), "worlds", $name);

        if (is_dir($path)) {
            FactionAPI::renameWorld($name, $newName);

            if (($world = Base::getInstance()->getServer()->getWorldManager()->getWorldByName($newName)) instanceof World) {
                Base::getInstance()->getServer()->getWorldManager()->unloadWorld($world, true);
            }
        }
    }

    public static function renameWorld(string $oldName, string $newName): void
    {
        Base::getInstance()->getServer()->getWorldManager()->loadWorld($oldName);

        if (($world = Base::getInstance()->getServer()->getWorldManager()->getWorldByName($oldName)) !== null) {
            Base::getInstance()->getServer()->getWorldManager()->unloadWorld($world, true);
        } else {
            return;
        }

        $from = Base::getInstance()->getServer()->getDataPath() . "/worlds/" . $oldName;
        $to = Base::getInstance()->getServer()->getDataPath() . "/worlds/" . $newName;

        rename($from, $to);

        if (Base::getInstance()->getServer()->getWorldManager()->isWorldLoaded($oldName)) {
            Base::getInstance()->getServer()->getWorldManager()->loadWorld($oldName, true);
        }
        $newWorld = Base::getInstance()->getServer()->getWorldManager()->getWorldByName($newName);

        if (!$newWorld instanceof World) {
            return;
        }
        $worldData = $newWorld->getProvider()->getWorldData();

        if (!$worldData instanceof BaseNbtWorldData) {
            return;
        }
        $worldData->getCompoundTag()->setString("LevelName", $newName);

        Base::getInstance()->getServer()->getWorldManager()->unloadWorld($newWorld);

        if (Base::getInstance()->getServer()->getWorldManager()->isWorldLoaded($newName)) {
            Base::getInstance()->getServer()->getWorldManager()->loadWorld($newName, true);
        }
    }

    public static function canBuild(Player $player, Block $block, string $type): bool
    {
        $session = Session::get($player);
        $faction = $session->data["player"]["faction"];

        if ($type === "break" && $session->inCooldown("_antibuild")) {
            $player->sendTip(Util::PREFIX . "Veuillez attendre §e" . ($session->getCooldownData("_antibuild")[0] - time()) . " §fseconde(s) avant de construire");
            return false;
        } else if ($player->getGamemode() === GameMode::CREATIVE() && $player->hasPermission("pocketmine.group.operator")) {
            return true;
        } else if ($player->getWorld()->getFolderName() === "island-" . $faction) {
            $x = $block->getPosition()->getX();
            $z = $block->getPosition()->getZ();

            $min = Cache::$factions[$faction]["island"]["zone"]["min"];
            $max = Cache::$factions[$faction]["island"]["zone"]["max"];

            if ($x >= $min && $x <= $max && $z >= $min && $z <= $max) {
                return true;
            } else {
                $player->sendMessage(Util::PREFIX . "Vous ne pouvez rien faire ici, vous devez augmenter le diametre de votre ile !");
                return false;
            }
        } else {
            $plot = FactionAPI::inPlot($block->getPosition()->getX(), $block->getPosition()->getZ());

            if ($plot[0]) {
                if ($type === "interact") {
                    $type = match (true) {
                        $block instanceof Door => "door",
                        $block instanceof Trapdoor => "trapdoor",
                        $block instanceof FenceGate => "fence-gates",
                        $block instanceof Chest, $block instanceof Barrel => "chest",
                        default => null
                    };
                } else if ($type === "break" && ($block instanceof Chest || $block instanceof Barrel)) {
                    $type = "chest";
                }

                $permission = is_null($type) ? true : FactionAPI::hasPermission($player, $type);

                if (is_bool($permission)) {
                    if ($type === "interact" || $permission) {
                        return $plot[1] == $faction;
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

        $faction = $session->data["player"]["faction"];
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
                } else if ($rank === $value) {
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

            $key = $session->data["player"]["faction"];
        }

        if (!self::exist($key)) {
            if ($session instanceof Session) {
                $session->data["player"]["faction"] = null;
                $session->data["player"]["faction_chat"] = false;
            }
            return null;
        }
        $members = Cache::$factions[$key]["members"];

        if ($members["leader"] === $value) {
            return "leader";
        } else if (in_array($value, $members["officiers"])) {
            return "officier";
        } else if (in_array($value, $members["members"])) {
            return "member";
        } else if (in_array($value, $members["recruits"])) {
            return "recruit";
        } else {
            if ($session instanceof Session) {
                $session->data["player"]["faction"] = null;
                $session->data["player"]["faction_chat"] = false;
            }
            return null;
        }
    }

    public static function inPlot(float $x, float $z): array
    {
        foreach (Cache::$plots as $id => $value) {
            if (!isset($value["min_x"]) || !isset($value["min_z"]) || !isset($value["max_x"]) || !isset($value["max_z"])) {
                continue;
            }

            $minX = min($value["min_x"], $value["max_x"]);
            $minZ = min($value["min_z"], $value["min_z"]);

            $maxX = max($value["max_x"], $value["max_x"]);
            $maxZ = max($value["max_z"], $value["max_z"]);

            if ($x >= $minX && $x <= $maxX && $z >= $minZ && $z <= $maxZ) {
                $faction = is_null($value["faction"]) ? "Aucune faction" : $value["faction"];
                return [true, $faction, $id];
            }
        }
        return [false, "Nature", 0];
    }

    public static function hasFaction(Player $player): bool
    {
        self::getFactionRank($player);
        return !is_null(Session::get($player)->data["player"]["faction"]);
    }
}