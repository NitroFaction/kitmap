<?php

namespace NCore\entity\entities;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\type\InvMenuTypeIds;
use NCore\handler\Cache;
use NCore\handler\OtherAPI;
use pocketmine\entity\Villager;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\inventory\Inventory;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use Util\item\items\custom\Armor;
use Util\item\items\custom\Sword;
use Util\item\items\IrisGlove;
use Util\util\IdsUtils;

class ForgeronEntity extends Villager
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

                    if (!$item instanceof Sword && !$item instanceof Armor && !$item instanceof IrisGlove) {
                        return $transaction->discard();
                    }
                    return $transaction->continue();
                });

                $menu->setInventoryCloseListener(function (Player $player, Inventory $inventory): void {
                    $items = $inventory->getContents();

                    $result = [
                        "iris" => 0,
                        "emerald" => 0
                    ];

                    foreach ($items as $item) {
                        $data = Cache::$config["ids"][strval($item->getId())];
                        $name = $data[0];

                        $max = intval($data[1] * $item->getCount());
                        $num = mt_rand(round($data[1] / 3) * $item->getCount(), $max);

                        $result[$name] = $result[$name] + $num;
                    }

                    foreach ($result as $key => $data) {
                        $max = floor($data / 9);
                        $min = $data - ($max * 9);

                        $maxId = match ($key) {
                            "iris" => IdsUtils::IRIS_INGOT,
                            default => ItemIds::EMERALD_BLOCK
                        };

                        $minId = match ($key) {
                            "iris" => IdsUtils::IRIS_FRAGMENT,
                            default => ItemIds::EMERALD
                        };

                        $maxItem = ItemFactory::getInstance()->get($maxId, 0, $max);
                        $minItem = ItemFactory::getInstance()->get($minId, 0, $min);

                        OtherAPI::addItem($player, $maxItem);
                        OtherAPI::addItem($player, $minItem);

                        $result[$key] = [$max, $min];
                    }

                    $player->sendMessage("§f[§eForgeron§f] §e§l» §r§fMMMhhh.. Je réussi à te récupérer §e" . $result["iris"][0] . " §flingot(s) d'iris, §e" . $result["iris"][1] . " §ffragment(s) d'iris, §e" . $result["emerald"][0] . " §fbloc(s) d'émeraude et pour finir §e" . $result["emerald"][1] . " §flingot(s) d'émeraude !");
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