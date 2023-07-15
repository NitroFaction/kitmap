<?php

namespace Kitmap\handler;

use Kitmap\entity\LogoutEntity;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use WeakMap;

class Cache
{
    use SingletonTrait;

    public static array $players;
    public static array $data;
    public static array $config;
    public static array $market;
    public static array $bans;
    public static array $claims;
    public static array $factions;

    /* @var array<string, LogoutEntity> */
    public static array $logouts;

    /* @var WeakMap<Player, boolean> */
    public static WeakMap $scoreboardPlayers;
    /* @var WeakMap<Player, boolean> */
    public static WeakMap $borderPlayers;
    /* @var WeakMap<Player, boolean> */
    public static WeakMap $combatPlayers;

    public function __construct()
    {
        $this->setInstance($this);

        self::$scoreboardPlayers ??= new WeakMap();
        self::$borderPlayers ??= new WeakMap();
        self::$combatPlayers ??= new WeakMap();

        @mkdir(Main::getInstance()->getDataFolder() . "data/");
        @mkdir(Main::getInstance()->getDataFolder() . "data/players");
        @mkdir(Main::getInstance()->getDataFolder() . "data/inventories/");

        Main::getInstance()->saveResource("config.json", true);

        self::$config = Util::getFile("config")->getAll();
        self::$data = Util::getFile("data/data")->getAll();
        self::$market = Util::getFile("data/market")->getAll();
        self::$bans = Util::getFile("data/bans")->getAll();
        self::$claims = Util::getFile("data/claims")->getAll();
        self::$factions = Util::getFile("data/factions")->getAll();

        foreach (Util::listAllFiles(Main::getInstance()->getDataFolder() . "data/players") as $file) {
            $path = pathinfo($file);
            $username = $path["filename"];

            $file = Util::getFile("data/players/" . $username);

            self::$players["money"][$username] = $file->get("money");
            self::$players["kill"][$username] = $file->get("kill");
            self::$players["death"][$username] = $file->get("death");
            self::$players["killstreak"][$username] = $file->get("killstreak");
            self::$players["played_time"][$username] = $file->get("played_time");
            self::$players["upper_name"][$username] = $file->get("upper_name");

            foreach (Cache::$config["saves"] as $column) {
                self::$players[$column][$username] = $file->get($column, []);
            }
        }
    }

    public function saveAll(): void
    {
        $this->save(self::$data, "data");
        $this->save(self::$market, "market");
        $this->save(self::$bans, "bans");
        $this->save(self::$bans, "claims");
        $this->save(self::$factions, "factions");
    }

    private function save(array $array, string $file): void
    {
        $file = Util::getFile("data/" . $file);

        $file->setAll($array);
        $file->save();
    }
}