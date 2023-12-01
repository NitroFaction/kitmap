<?php

namespace Kitmap\task\repeat;

use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\Server;
use skymin\bossbar\BossBarAPI;

class FarmingWarsTask
{

    public static bool $currentFarmingWars = false;
    public static ?Block $block = null;
    public static array $leaderboard = [];
    // public static int $time = 900;
    public static int $time = 60;

    public static function run(): void
    {
        $players = Main::getInstance()->getServer()->getOnlinePlayers();

        if (!self::$currentFarmingWars) {
            return;
        }

        foreach ($players as $player) {
            // $percentage = self::$time / 900;
            $percentage = self::$time / 60;

            $leaderboard = self::getArsortedLeaderboard();
            $bestPlayer = empty(self::$leaderboard) ? "Aucun" : array_keys($leaderboard)[0];

            $title = $bestPlayer !== "Aucun"
                ? "FarmingWars | Top 1 -> " . $bestPlayer . " avec " . $leaderboard[$bestPlayer] . " " . self::getTranslatedName(self::$block)
                : "FarmingWars | Top 1 -> Aucun";

            BossBarAPI::getInstance()->sendBossBar($player, $title, 3, $percentage, BossBarAPI::COLOR_GREEN);
        }

        self::$time--;

        if (self::$time <= 0) {
            if (!empty(self::$leaderboard)) {
                $leaderboard = self::getArsortedLeaderboard();
                $podium = array_slice(array_keys($leaderboard), 0, 3);

                Server::getInstance()->broadcastMessage("§l§q» §r§aRésultats de l'événement FarmingWars §l§q«§r");
                $i = 0;

                foreach ($podium as $key) {
                    $pack = 6 - $i;
                    $player = Server::getInstance()->getPlayerExact($key);

                    if ($player instanceof Player) {
                        $session = Session::get($player);
                        $session->addValue("pack", $pack);
                    }

                    $position = array_search($key, $podium) + 1;
                    Server::getInstance()->broadcastMessage("§a#" . $position . ". §f" . $key . " §8(§7" . self::$leaderboard[$key] . "§8) [§7+" . $pack . " packs§8]");

                    $i += 2;
                }
            } else {
                Server::getInstance()->broadcastMessage(Util::PREFIX . "Aucun joueur n'a participé à l'événement §qFarmingWars§f... Par conséquent, personne n'en ressort vainqueur.");
            }

            self::reset();
        }
    }

    public static function start(Block $block): void
    {
        self::$currentFarmingWars = true;
        self::$block = $block;
    }

    public static function reset(): void
    {
        self::$currentFarmingWars = false;
        self::$block = null;
        self::$leaderboard = [];
        // self::$time = 900;
        self::$time = 60;

        foreach (Main::getInstance()->getServer()->getOnlinePlayers() as $player) {
            BossBarAPI::getInstance()->hideBossBar($player, 3);
        }
    }

    public static function updateScore(Player $player, int $addition): void
    {
        $name = $player->getName();
        self::$leaderboard[$name] = (self::$leaderboard[$name] ?? 0) + $addition;
    }

    public static function getArsortedLeaderboard(): array
    {
        $leaderboard = self::$leaderboard;
        arsort($leaderboard);
        return $leaderboard;
    }

    /**
     * @return Block[]
     */
    public static function getAllCrops(): array
    {
        return [
            VanillaBlocks::CARROTS(),
            VanillaBlocks::POTATOES(),
            VanillaBlocks::BEETROOTS(),
            VanillaBlocks::WHEAT(),
            VanillaBlocks::MELON(),
            VanillaBlocks::SUGARCANE(),
            VanillaBlocks::BAMBOO(),
            VanillaBlocks::NETHER_WART()
        ];
    }

    public static function getItemByBlock(Block $block): Item
    {
        return match ($block->getTypeId()) {
            BlockTypeIds::CARROTS => VanillaItems::CARROT(),
            BlockTypeIds::POTATOES => VanillaItems::POTATO(),
            BlockTypeIds::BEETROOTS => VanillaItems::BEETROOT(),
            BlockTypeIds::WHEAT => VanillaItems::WHEAT(),
            BlockTypeIds::MELON => VanillaItems::MELON(),
            BlockTypeIds::SUGARCANE => VanillaBlocks::SUGARCANE()->asItem(),
            BlockTypeIds::BAMBOO => VanillaItems::BAMBOO(),
            BlockTypeIds::NETHER_WART => VanillaItems::RABBIT_FOOT()
        };
    }

    public static function getTranslatedName(Block $block): string
    {
        return match ($block->getTypeId()) {
            BlockTypeIds::CARROTS => "Carotte(s)",
            BlockTypeIds::POTATOES => "Patate(s)",
            BlockTypeIds::BEETROOTS => "Betterave(s)",
            BlockTypeIds::WHEAT => "Blé(s)",
            BlockTypeIds::MELON => "Melon(s)",
            BlockTypeIds::SUGARCANE => "Canne(s) à sucre",
            BlockTypeIds::BAMBOO => "Bambou(s)",
            BlockTypeIds::NETHER_WART => "Poussière(s) d'iris"
        };
    }

}