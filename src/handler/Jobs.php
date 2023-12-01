<?php

namespace Kitmap\handler;

use Kitmap\Session;
use Kitmap\Util;
use pocketmine\player\Player;
use pocketmine\world\sound\BlazeShootSound;

class Jobs
{
    public static function getProgressBar(Player $player, string $job, string $option = null): string
    {
        $level = self::getLevel($player, $job);
        $xp = self::getXp($player, $job);

        $nextXp = Cache::$config["jobs"]["lvls"][$level];

        if ($option === "UI") {
            if ($level === 20) {
                return "0§q/§8-1 §q- §8Level: §q" . $level;
            } else {
                return $xp . "§q/§8" . $nextXp . " §q- §8Level: §q" . $level;
            }
        }

        if ($level === 20) {
            return "§cNiveau maximum atteint";
        } else {
            $progress = max(1, round((($xp / $nextXp) * 100) / 2, 2));
            return "§a" . str_repeat("|", $progress) . "§c" . str_repeat("|", 50 - $progress);
        }
    }

    public static function getLevel(Player $player, string $job): int
    {
        return Session::get($player)->data["jobs"][$job]["lvl"];
    }

    public static function getXp(Player $player, string $job): int
    {
        return Session::get($player)->data["jobs"][$job]["xp"];
    }

    public static function addXp(Player $player, string $job, int $xp, bool $tip = true): void
    {
        if ($player->isCreative()) {
            return;
        }

        $session = Session::get($player);

        $rank = Rank::getEqualRank($player->getName());
        $tax = Rank::getRankValue($rank, "tax");

        $level = self::getLevel($player, $job);
        $xp = ($level === 20) ? 0 : round($xp * (1 + (25 - $tax) / 100));

        $nextTotal = Cache::$config["jobs"]["lvls"][$level];
        $total = self::getXp($player, $job) + $xp;

        if ($tip) {
            $player->sendTip(Util::PREFIX . "+ §q" . $xp . " §f" . $job);
        }

        if ($total > $nextTotal) {
            $newXp = $total - $nextTotal;
            $nextLevel = $level + 1;

            $session->data["jobs"][$job]["lvl"] = $nextLevel;
            $session->data["jobs"][$job]["xp"] = $newXp;

            $session->addValue("money", $nextLevel * 2000);

            $player->sendMessage(Util::PREFIX . "Vous venez de passer niveau §q" . $nextLevel . " §fdu métier de §q" . $job . " §f!!");
            $player->sendMessage(Util::PREFIX . "Vous venez de recevoir §q" . $nextLevel * 2000 . " §fpièces pour vos récompenses de jobs !");

            $player->broadcastSound(new BlazeShootSound());

            if (isset(Cache::$config["jobs"]["rewards"][strval($nextLevel)])) {
                $data = Cache::$config["jobs"]["rewards"][strval($nextLevel)];
                $data = explode(":", $data);

                switch (intval($data[0])) {
                    case 0:
                        $name = intval($data[1]);
                        $count = intval($data[2]);

                        $item = Util::getItemByName($name)->setCount($count);
                        Util::addItem($player, $item);

                        $player->sendMessage(Util::PREFIX . "Vous venez de recevoir §q" . $data[4] . " §fpour vos récompenses de jobs !");
                        break;
                    case 1:
                        $name = intval($data[1]);
                        $customName = $data[2];
                        $type = intval($data[3]);
                        $_data = intval($data[4]);

                        $item = Util::getItemByName($name);
                        $item = Pack::initializeItem($item, [$customName, $type, $_data]);

                        Util::addItem($player, $item);
                        $player->sendMessage(Util::PREFIX . "Vous venez de recevoir §q" . $data[6] . " §fpour vos récompenses de jobs !");
                        break;
                    case 2:
                        $partneritems = array_keys(Cache::$config["partneritems"]);
                        $item = $partneritems[array_rand($partneritems)];

                        list(, , , $customName) = explode(":", Cache::$config["partneritems"][$item]);

                        if ($item === "pumpkinaxe") {
                            $item = PartnerItems::createItem($item)->setCount(3);
                        } else {
                            $item = PartnerItems::createItem($item)->setCount(12);
                        }

                        Util::addItem($player, $item);
                        $player->sendMessage(Util::PREFIX . "Vous venez de recevoir un(e) §q" . $customName . " §fpour vos récompenses de jobs !");
                        break;
                }
            }
        } else {
            $actualXp = self::getXp($player, $job);
            $session->data["jobs"][$job]["xp"] = $actualXp + $xp;
        }
    }
}