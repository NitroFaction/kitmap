<?php

namespace NCore\handler;

use NCore\Session;
use NCore\Util;
use pocketmine\item\ItemFactory;
use pocketmine\player\Player;
use pocketmine\world\sound\BlazeShootSound;

class JobsAPI
{
    public static function getProgressBar(Player $player, string $job, string $option = null): string
    {
        $level = self::getLevel($player, $job);
        $xp = self::getXp($player, $job);

        $nextXp = Cache::$config["jobs"]["lvls"][$level];

        if ($option === "UI") {
            if ($level === 20) {
                return "0§e/§8-1 §e- §8Level: §e" . $level;
            } else {
                return $xp . "§e/§8" . $nextXp . " §e- §8Level: §e" . $level;
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
        return Session::get($player)->data["player"]["jobs"][$job]["lvl"];
    }

    public static function getXp(Player $player, string $job): int
    {
        return Session::get($player)->data["player"]["jobs"][$job]["xp"];
    }

    public static function addXp(Player $player, string $job, int $xp, bool $tip = true): void
    {
        if ($player->isCreative()) {
            return;
        }

        $session = Session::get($player);

        $rank = RankAPI::getEqualRank($player->getName());
        $tax = RankAPI::getRankValue($rank, "tax");

        $level = self::getLevel($player, $job);
        $xp = ($level === 20) ? 0 : round($xp * (1 + (25 - $tax) / 100));

        $nextTotal = Cache::$config["jobs"]["lvls"][$level];
        $total = self::getXp($player, $job) + $xp;

        if ($tip) {
            $player->sendTip(Util::PREFIX . "+ §e" . $xp . " §f" . $job);
        }

        if ($total > $nextTotal) {
            $newXp = $total - $nextTotal;
            $nextLevel = $level + 1;

            $session->data["player"]["jobs"][$job]["lvl"] = $nextLevel;
            $session->data["player"]["jobs"][$job]["xp"] = $newXp;

            $session->addValue("money", $nextLevel * 2000);

            $player->sendMessage(Util::PREFIX . "Vous venez de passer niveau §e" . $nextLevel . " §fdu métier de §e" . $job . " §f!!");
            $player->sendMessage(Util::PREFIX . "Vous venez de recevoir §e" . $nextLevel * 2000 . " §fpièces pour vos récompenses de jobs !");

            $player->broadcastSound(new BlazeShootSound());

            if (isset(Cache::$config["jobs"]["rewards"][strval($nextLevel)])) {
                $data = Cache::$config["jobs"]["rewards"][strval($nextLevel)];
                $data = explode(":", $data);

                switch (intval($data[0])) {
                    case 0:
                        $id = intval($data[1]);
                        $damage = intval($data[2]);
                        $count = intval($data[3]);

                        $item = ItemFactory::getInstance()->get($id, $damage, $count);
                        OtherAPI::addItem($player, $item);

                        $player->sendMessage(Util::PREFIX . "Vous venez de recevoir §e" . $data[4] . " §fpour vos récompenses de jobs !");
                        break;
                    case 1:
                        $id = intval($data[1]);
                        $damage = intval($data[2]);
                        $customName = $data[3];
                        $type = intval($data[4]);
                        $_data = intval($data[5]);

                        $item = ItemFactory::getInstance()->get($id, $damage);
                        $item = PackAPI::initializeItem($item, [$customName, $type, $_data]);

                        OtherAPI::addItem($player, $item);
                        $player->sendMessage(Util::PREFIX . "Vous venez de recevoir §e" . $data[6] . " §fpour vos récompenses de jobs !");
                        break;
                    case 2:
                        $partneritems = array_keys(Cache::$config["partneritems"]);
                        $item = $partneritems[array_rand($partneritems)];

                        list(, , , $customName) = explode(":", Cache::$config["partneritems"][$item]);

                        if ($item === "pumpkinaxe") {
                            $item = PartnerItemsAPI::createItem($item)->setCount(3);
                        } else {
                            $item = PartnerItemsAPI::createItem($item)->setCount(12);
                        }

                        OtherAPI::addItem($player, $item);
                        $player->sendMessage(Util::PREFIX . "Vous venez de recevoir un(e) §e" . $customName . " §fpour vos récompenses de jobs !");
                        break;
                }
            }
        } else {
            $actualXp = self::getXp($player, $job);
            $session->data["player"]["jobs"][$job]["xp"] = $actualXp + $xp;
        }
    }
}