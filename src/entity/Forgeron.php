<?php

namespace Kitmap\entity;

use Kitmap\Util;
use MaXoooZ\Util\item\Crafts;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\entity\Villager;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\inventory\Inventory;
use pocketmine\item\Armor;
use pocketmine\item\Item;
use pocketmine\item\Sword;
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

                    if (!$item instanceof Sword && !$item instanceof Armor) {
                        return $transaction->discard();
                    }
                    return $transaction->continue();
                });

                $menu->setInventoryCloseListener(function (Player $player, Inventory $inventory): void {
                    $shapes = Crafts::$uncondenseShapes;
                    $items = $inventory->getContents();

                    $give = 0;

                    foreach ($items as $item) {
                        foreach ($shapes as $shape) {
                            $output = $shape["output"];
                            $input = $shape["input"];

                            $count = $shape["count"];

                            if ($output instanceof Item && $input instanceof Item && $output->getTypeId() === $item->getTypeId()) {
                                $rand = mt_rand(1, ceil($count / 3));
                                Util::addItem($player, $input->setCount($rand));
                                $give++;
                            }
                        }
                    }

                    if ($give > 0) {
                        $player->sendMessage(Util::PREFIX . "Vous venez de recevoir les items décondensé");
                    }
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