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
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\data\BaseNbtWorldData;
use pocketmine\world\Position;
use pocketmine\world\World;
use Symfony\Component\Filesystem\Path;

class Faction
{
    public static function getNextRank(string $rank): string
    {
        $ranks = array_keys(Cache::$config["faction-ranks"]);
        return $ranks[self::getRankPosition($rank) - 1] ?? $rank;
    }

    public static function getRankPosition(string $rank): int
    {
        $ranks = array_keys(Cache::$config["faction-ranks"]);
        return array_search($rank, $ranks);
    }

    public static function getPreviousRank(string $rank): string
    {
        $ranks = array_keys(Cache::$config["faction-ranks"]);
        return $ranks[self::getRankPosition($rank) + 1] ?? $rank;
    }

    public static function deleteBox(string $key): void
    {
        $name = "box-" . $key;
        $newName = $name . "-deleted-" . time();

        $path = Path::join(Main::getInstance()->getServer()->getDataPath(), "worlds", $name);

        if (is_dir($path)) {
            self::renameWorld($name, $newName);

            if (($world = Main::getInstance()->getServer()->getWorldManager()->getWorldByName($newName)) instanceof World) {
                Main::getInstance()->getServer()->getWorldManager()->unloadWorld($world, true);
            }
        }
    }

    public static function renameWorld(string $oldName, string $newName): void
    {
        Main::getInstance()->getServer()->getWorldManager()->loadWorld($oldName);

        if (($world = Main::getInstance()->getServer()->getWorldManager()->getWorldByName($oldName)) !== null) {
            Main::getInstance()->getServer()->getWorldManager()->unloadWorld($world, true);
        } else {
            return;
        }

        $from = Main::getInstance()->getServer()->getDataPath() . "/worlds/" . $oldName;
        $to = Main::getInstance()->getServer()->getDataPath() . "/worlds/" . $newName;

        rename($from, $to);

        if (Main::getInstance()->getServer()->getWorldManager()->isWorldLoaded($oldName)) {
            Main::getInstance()->getServer()->getWorldManager()->loadWorld($oldName, true);
        }
        $newWorld = Main::getInstance()->getServer()->getWorldManager()->getWorldByName($newName);

        if (!$newWorld instanceof World) {
            return;
        }
        $worldData = $newWorld->getProvider()->getWorldData();

        if (!$worldData instanceof BaseNbtWorldData) {
            return;
        }
        $worldData->getCompoundTag()->setString("LevelName", $newName);

        Main::getInstance()->getServer()->getWorldManager()->unloadWorld($newWorld);

        if (Main::getInstance()->getServer()->getWorldManager()->isWorldLoaded($newName)) {
            Main::getInstance()->getServer()->getWorldManager()->loadWorld($newName, true);
        }
    }

    public static function isBoxLocked(string $key): bool
    {
        return self::exists($key) ? Cache::$factions[$key]["box"]["lock"] : false;
    }

    public static function exists(?string $key): bool
    {
        return !is_null($key) && isset(Cache::$factions[strtolower($key)]);
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

    public static function canBuild(Player $player, Block|Position $block, string $type): bool
    {
        $session = Session::get($player);
        $faction = $session->data["faction"];

        $position = $block instanceof Position ? $block : $block->getPosition();

        if ($type === "break" && $session->inCooldown("_antibuild")) {
            $player->sendTip(Util::PREFIX . "Veuillez attendre §6" . ($session->getCooldownData("_antibuild")[0] - time()) . " §fseconde(s) avant de construire");
            return false;
        } else if ($player->getGamemode() === GameMode::CREATIVE() && $player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            return true;
        } else if ($player->getWorld()->getFolderName() === "box-" . $faction) {
            $position = $block instanceof Position ? $block : $block->getPosition();

            $x = $position->getX();
            $z = $position->getZ();

            $min = Cache::$factions[$faction]["box"]["zone"]["min"];
            $max = Cache::$factions[$faction]["box"]["zone"]["max"];

            if ($x >= $min && $x <= $max && $z >= $min && $z <= $max) {
                return true;
            } else {
                $player->sendMessage(Util::PREFIX . "Vous ne pouvez rien faire ici, vous devez augmenter le diametre de votre ile !");
                return false;
            }
        } else {
            $claim = Faction::inClaim($position->getX(), $position->getZ());

            if ($claim[0]) {
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
            $require = $data["permissions"][$permission] ?? null;

            if (is_null($require)) {
                return true;
            }

            $passed = false;

            if ($rank === $require) {
                return true;
            }

            foreach (array_keys(Cache::$config["faction-ranks"]) as $value) {
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
        } else if (in_array($value, $members["officiers"])) {
            return "officier";
        } else if (in_array($value, $members["members"])) {
            return "member";
        } else if (in_array($value, $members["recruits"])) {
            return "recruit";
        } else {
            if ($session instanceof Session) {
                $session->data["faction"] = null;
                $session->data["faction_chat"] = false;
            }
            return null;
        }
    }

    public static function inClaim(int|float $x, int|float $z): array
    {
        $chunkX = intval(floor($x)) >> Chunk::COORD_BIT_SIZE;
        $chunkZ = intval(floor($z)) >> Chunk::COORD_BIT_SIZE;

        $chunk = $chunkX . ":" . $chunkZ;

        if (isset(Cache::$claims[$chunk])) {
            return [true, Cache::$claims[$chunk], $chunk];
        } else {
            return [false, null, $chunk];
        }
    }

    public static function canClaim(Position $position): bool
    {
        $chunkX = $position->getFloorX() >> Chunk::COORD_BIT_SIZE;
        $chunkZ = $position->getFloorZ() >> Chunk::COORD_BIT_SIZE;

        return in_array($chunkX . ":" . $chunkZ, Cache::$data["claims"]);
    }

    public static function hasFaction(Player $player): bool
    {
        self::getFactionRank($player);
        return !is_null(Session::get($player)->data["faction"]);
    }
}