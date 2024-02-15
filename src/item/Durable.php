<?php

namespace Kitmap\item;

use pocketmine\event\inventory\ItemDamageEvent;

abstract class Durable extends Item
{
    const DAMAGE_TAG = "nitro_damage";

    public function onDamage(ItemDamageEvent $event): bool
    {
        $item = $event->getItem();
        $amount = $event->getDamage() - $event->getUnbreakingDamageReduction();

        $baseDurability = $item->getMaxDurability();
        $newDurability = $this->getMaxDurability();

        if ($item->getDamage() > 1 && is_null($item->getNamedTag()->getTag(self::DAMAGE_TAG))) {
            $item->getNamedTag()->setInt(self::DAMAGE_TAG, ceil(($amount / $newDurability) * $baseDurability));
        }

        $amount = $item->getNamedTag()->getInt(self::DAMAGE_TAG, 0) + $amount;

        $event->setDamage(min(ceil(($amount / $newDurability) * $baseDurability), $item->getMaxDurability()) - $item->getDamage());
        $event->setUnbreakingDamageReduction(0);

        $item->getNamedTag()->setInt(self::DAMAGE_TAG, $amount);
        return false;
    }

    abstract public function getMaxDurability(): int;
}