<?php

namespace NCore;

use NCore\handler\Cache;
use NCore\handler\SanctionAPI;
use pocketmine\block\Block;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use Util\util\IdsUtils;

class Util
{
    const PREFIX = "§e§l» §r§f";

    public static function arrayToPage(array $array, ?int $page, int $separator): array
    {
        $result = [];

        $pageMax = ceil(count($array) / $separator);
        $min = ($page * $separator) - $separator;

        $count = 1;
        $max = $min + $separator;

        foreach ($array as $item) {
            if ($count > $max) {
                continue;
            } else if ($count > $min) {
                $result[] = $item;
            }
            $count++;
        }
        return [$pageMax, $result];
    }

    public static function allSelectorExecute(CommandSender $sender, string $command, array $args): void
    {
        if (!$sender->hasPermission("pocketmine.group.operator")) {
            $sender->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de faire cela");
            return;
        }

        foreach (Base::getInstance()->getServer()->getOnlinePlayers() as $player) {
            $cmd = $command . " " . implode(" ", $args);
            $cmd = str_replace("@a", "\"" . $player->getName() . "\"", $cmd);

            self::executeCommand($cmd);
        }
    }

    public static function executeCommand(string $command): void
    {
        $server = Base::getInstance()->getServer();
        $server->dispatchCommand(new ConsoleCommandSender($server, $server->getLanguage()), $command);
    }

    public static function getFile($name): Config
    {
        return new Config(Base::getInstance()->getDataFolder() . "data/" . $name . ".json", Config::JSON);
    }

    public static function isPlayerAimOnAntiBack(Player $player): bool
    {
        $blocks = $player->getLineOfSight(10, 1);

        for ($i = 0; $i <= 2; $i++) {
            $block = $blocks[$i] ?? null;

            if ($block instanceof Block) {
                return $block->getId() === IdsUtils::ANTIBACK_BLOCK || ($block->getId() === IdsUtils::GlASS_ANTIBACK_BLOCK && $block->getMeta() === IdsUtils::GlASS_ANTIBACK_META);
            }
        }
        return false;
    }

    public static function arrayToMessage(array $array, ?int $page, string $message, bool $seconds = false): array
    {
        $result = [];

        $pageMax = ceil(count($array) / 10);
        $min = ($page * 10) - 10;

        $count = 1;
        $max = $min + 10;

        foreach ($array as $key => $value) {
            $value = $seconds ? SanctionAPI::format($value) : $value;

            if ($count > $max) {
                continue;
            } else if ($count > $min) {
                $result[] = str_replace(["{KEY}", "{VALUE}"], [$key, $value], $message);
            }
            $count++;
        }
        return [$pageMax, $result];
    }

    public static function antiBlockGlitch(Player $player): void
    {
        $session = Session::get($player);
        $delay = round(7 * (max($player->getNetworkSession()->getPing(), 50) / 50));

        if (!$session->inCooldown("enderpearl")) {
            $session->setCooldown("enderpearl", ceil($delay / 20), [$player->getPosition()]);
        }

        $player->teleport($player->getPosition(), 180, -90);
        $position = $player->getPosition();

        Base::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player, $position) {
            if ($player->isOnline()) {
                $player->teleport($position, 180, -90);
            }
        }), $delay);
    }

    public static function formatTitle(string $title): string
    {
        $result = "";

        foreach (str_split(TextFormat::clean($title)) as $caracter) {
            $result .= self::stringToUnicode($caracter) . " ";
        }
        return trim($result);
    }

    public static function stringToUnicode(string $str): string
    {
        return Cache::$config["unicodes"][strtolower($str)] ?? " ";
    }
}