<?php

namespace NCore\handler;

use NCore\Base;
use NCore\entity\entities\LogoutEntity;
use NCore\Util;
use Webmozart\PathUtil\Path;

class Cache
{
    public static array $players;
    public static array $config;
    public static array $auctionhouse;
    public static array $bans;
    public static array $dynamic;
    public static array $factions;
    public static array $plots;

    /* @var array<string, LogoutEntity> */
    public static array $logouts;

    public static function loadCache(): void
    {
        @mkdir(Base::getInstance()->getDataFolder() . "data/");
        @mkdir(Base::getInstance()->getDataFolder() . "data/players");
        @mkdir(Base::getInstance()->getDataFolder() . "data/inventories/");
        @mkdir(Base::getInstance()->getDataFolder() . "data/skins/");

        Base::getInstance()->saveResource("config.yml", true);

        Cache::$config = Base::getInstance()->getConfig()->getAll();
        Cache::$auctionhouse = Util::getFile("auctionhouse")->getAll();
        Cache::$bans = Util::getFile("bans")->getAll();
        Cache::$dynamic = Util::getFile("dynamic")->getAll();
        Cache::$factions = Util::getFile("factions")->getAll();
        Cache::$plots = Util::getFile("plots")->getAll();

        foreach (OtherAPI::listAllFiles(Path::join(Base::getInstance()->getFile(), "resources", "skin")) as $file) {
            $data = pathinfo($file);

            $dirs = explode(DIRECTORY_SEPARATOR, $data["dirname"]);
            $name = end($dirs);

            switch ($data["extension"]) {
                case "json":
                    SkinAPI::$skins[$name]["geometry"] = file_get_contents($file);
                    break;
                case "png":
                    SkinAPI::$skins[$name]["texture"][$data["filename"]] = SkinAPI::getBytesFromImage($file);
                    break;
            }
        }

        foreach (OtherAPI::listAllFiles(Base::getInstance()->getDataFolder() . "data/players") as $file) {
            $path = pathinfo($file);
            $username = $path["filename"];

            $file = Util::getFile("players/" . $username);

            Cache::$players["money"][$username] = $file->get("money");
            Cache::$players["kill"][$username] = $file->get("kill");
            Cache::$players["death"][$username] = $file->get("death");
            Cache::$players["killstreak"][$username] = $file->get("killstreak");
            Cache::$players["played_time"][$username] = $file->get("played_time");
            Cache::$players["upper_name"][$username] = $file->get("upper_name");

            foreach (Cache::$config["saves"] as $column) {
                Cache::$players[$column][$username] = $file->get($column, []);
            }
        }
    }

    public static function saveCache(): void
    {
        self::save(self::$auctionhouse, "auctionhouse");
        self::save(self::$bans, "bans");
        self::save(self::$dynamic, "dynamic");
        self::save(self::$factions, "factions");
        self::save(self::$plots, "plots");
    }

    private static function save(array $array, string $file): void
    {
        $file = Util::getFile($file);

        $file->setAll($array);
        $file->save();
    }
}