<?php

namespace Kitmap\entity;

use Kitmap\handler\Cache;
use Kitmap\Util;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Villager;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\inventory\Inventory;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;

class Forgeron extends Villager
{
    public function attack(EntityDamageEvent $source): void
    {
        if ($source instanceof EntityDamageByEntityEvent) {
            $damager = $source->getDamager();

            if ($damager instanceof Player) {
                $menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);
                $menu->setName("Mettez vos items");

                $menu->setListener(function (InvMenuTransaction $transaction): InvMenuTransactionResult {
                    $item = $transaction->getIn();
                    $lore = $item->getLore()[0] ?? "";

                    if (str_contains($lore, "gambling")) {
                        return $transaction->discard();
                    }

                    $name = Util::reprocess($item->getVanillaName());

                    if (!isset(Cache::$config["forgeron"][$name])) {
                        return $transaction->discard();
                    }
                    return $transaction->continue();
                });

                $menu->setInventoryCloseListener(function (Player $player, Inventory $inventory): void {
                    $items = $inventory->getContents();

                    if (1 > count($items)) {
                        return;
                    }

                    $arr = [
                        "emerald" => 0,
                        "iris" => 0
                    ];

                    foreach ($items as $item) {
                        $name = Util::reprocess($item->getVanillaName());

                        $type = Cache::$config["forgeron"][$name]["type"];
                        $number = Cache::$config["forgeron"][$name]["number"];

                        $arr[$type] += intval($number);
                    }

                    $arr["iris"] = ceil($arr["iris"] * 0.75);
                    $arr["emerald"] = ceil($arr["emerald"] * 0.75);

                    $arr["iris"] = mt_rand(0, $arr["iris"]);
                    $arr["emerald"] = mt_rand(0, $arr["emerald"]);

                    $irisIngot = intval($arr["iris"] / 9);
                    $arr["iris"] = $arr["iris"] - ($irisIngot * 9);

                    $emeraldBlock = intval($arr["emerald"] / 9 / 9);
                    $arr["emerald"] = $arr["emerald"] - ($emeraldBlock * 9 * 9);

                    $emeraldIngot = intval($arr["emerald"] / 9);
                    $arr["emerald"] = $arr["emerald"] - ($emeraldIngot * 9);

                    $items = [
                        VanillaItems::RABBIT_HIDE()->setCount($irisIngot),
                        VanillaItems::RABBIT_FOOT()->setCount($arr["iris"]),
                        VanillaBlocks::EMERALD()->asItem()->setCount($emeraldBlock),
                        VanillaItems::EMERALD()->setCount($emeraldIngot),
                        VanillaItems::GOLD_NUGGET()->setCount($arr["emerald"]),
                    ];

                    $sentences = [
                        $irisIngot > 0 ? $irisIngot . " §flingot" . ($irisIngot > 1 ? "s" : "") . " d'iris" : "",
                        $arr["iris"] > 0 ? $arr["iris"] . " §ffragment" . ($arr["iris"] > 1 ? "s" : "") . " d'iris" : "",
                        $emeraldBlock > 0 ? $emeraldBlock . " §fblocs" . ($emeraldBlock > 1 ? "s" : "") . " d'émeraude" : "",
                        $emeraldIngot > 0 ? $emeraldIngot . " §flingot" . ($emeraldIngot > 1 ? "s" : "") . " d'émeraude" : "",
                        $arr["emerald"] > 0 ? $arr["emerald"] . " §fpépites" . ($arr["emerald"] > 1 ? "s" : "") . " d'émeraude" : ""
                    ];

                    $sentences = array_filter($sentences, fn($val) => $val !== "");
                    $sentence = implode(", §6", $sentences);

                    $player->getInventory()->addItem(...$items);

                    if (($lastCommaPosition = strrpos($sentence, ",")) !== false) {
                        $sentence = substr_replace($sentence, " et", $lastCommaPosition, 1);
                    }

                    $player->sendMessage(Util::PREFIX . "Le forgeron a travaillé dur sur vos items, au final il en a " . (strlen($sentence) > 1 ? "ressorti: §6" . $sentence . " !" : "rien ressorti, désolé"));
                });

                $menu->send($damager);
            }
        }

        $source->cancel();
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $this->setNameTag("Forgeron");
        $this->setNameTagAlwaysVisible();
    }
}