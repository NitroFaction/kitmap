<?php

namespace NCore\handler;

use NCore\Base;
use NCore\Session;
use NCore\Util;
use pocketmine\player\Player;

class RankAPI
{
    public static function existRank(string $rank): bool
    {
        return isset(Cache::$config["ranks"][$rank]);
    }

    public static function getEqualRank(string $name): string
    {
        $rank = self::getRank($name);

        if (self::isStaff($rank)) {
            return "roi";
        } else {
            if ($rank === "createur") {
                return "elite";
            }
            return $rank;
        }
    }

    public static function getRank(string $name): ?string
    {
        $name = strtolower($name);
        $player = Base::getInstance()->getServer()->getPlayerByPrefix($name);

        if ($player instanceof Player) {
            $session = Session::get($player);
            $rank = $session->data["player"]["rank"];
        } else {
            $file = Util::getFile("players/" . $name);
            $rank = $file->get("rank", "joueur");
        }
        return $rank;
    }

    public static function isStaff(?string $rank): bool
    {
        return in_array($rank, ["guide", "moderateur", "sm", "administrateur", "fondateur"]);
    }

    public static function hasRank(Player $player, string $rank): bool
    {
        $_rank = self::getRank($player->getName());

        if ($rank === $_rank) {
            return true;
        }

        if (!self::isStaff($_rank)) {
            $passed = false;

            foreach (array_keys(Cache::$config["ranks"]) as $value) {
                if (!$passed && $value === $_rank) {
                    return false;
                } else if ($rank === $value) {
                    $passed = true;
                }
            }
        }
        return true;
    }

    public static function setRank(string $name, string $rank): void
    {
        $name = strtolower($name);
        $player = Base::getInstance()->getServer()->getPlayerByPrefix($name);

        if ($player instanceof Player) {
            $session = Session::get($player);
            $session->data["player"]["rank"] = $rank;

            self::updateNameTag($player);
            self::addPermissions($player);
            self::saveRank($player->getXuid(), $rank);

            if (self::isStaff($session->data["player"]["rank"]) && !self::isStaff($rank)) {
                $player->kick(Util::PREFIX . "Reconnectez vous");
            }
        } else {
            $file = Util::getFile("players/" . $name);

            if ($file->getAll() !== []) {
                $file->set("rank", $rank);
                $file->save();

                self::saveRank($file->get("xuid"), $rank);
            }
        }
    }

    public static function updateNameTag(Player $player): void
    {
        $name = $player->getName();
        $rank = ($name === $player->getDisplayName()) ? self::getRank($name) : "joueur";

        $prefix = self::getRankValue($rank, "gamertag");
        $replace = self::setReplace($prefix, $player);

        $player->setNameTag($replace);
        $player->setNameTagAlwaysVisible();
    }

    public static function getRankValue(string $rank, string $value): mixed
    {
        return Cache::$config["ranks"][$rank][$value];
    }

    public static function setReplace(string $replace, Player $player, string $msg = ""): string
    {
        $session = Session::get($player);
        FactionAPI::hasFaction($player);

        $faction = $session->data["player"]["faction"];
        $faction = (is_null($faction)) ? "..." : Cache::$factions[$faction]["upper_name"];

        if (($tag = $session->data["player"]["tag"]) !== null) {
            $tag = Cache::$config["tags"][$tag] . " ";
        } else {
            $tag = "";
        }

        return str_replace(
            ["{tag}", "{name}", "{fac}", "{msg}"],
            [$tag, $player->getDisplayName(), $faction, $msg],
            $replace
        );
    }

    public static function addPermissions(Player $player): void
    {
        $session = Session::get($player);

        if (RankAPI::isStaff($session->data["player"]["rank"]) || $player->hasPermission("pocketmine.group.operator")) {
            $player->addAttachment(Base::getInstance(), "staff.group", true);
            $player->addAttachment(Base::getInstance(), "pocketmine.command.teleport", true);

            if (!in_array($session->data["player"]["rank"], ["guide", "moderateur"])) {
                $player->addAttachment(Base::getInstance(), "pocketmine.command.gamemode", true);
            }
        }
    }

    public static function saveRank(string $value, string $key): void
    {
        $file = Util::getFile("ownings");
        $data = $file->get($value) ?? [];

        $data["rank"] = $key;

        $file->set($value, $data);
        $file->save();
    }
}