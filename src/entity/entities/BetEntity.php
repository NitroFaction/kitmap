<?php

namespace Kitmap\entity\entities;

use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;

class BetEntity extends Human
{
    public function attack(EntityDamageEvent $source): void
    {
        $source->cancel();

        if ($source instanceof EntityDamageByEntityEvent) {
            $damager = $source->getDamager();

            if ($source->getCause() === EntityDamageEvent::CAUSE_ENTITY_ATTACK && $damager instanceof Player) {
                $damager->chat("/bet");
            }
        }
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        $this->setScale(1.2);
        $this->setNameTag("§7Paris Sportif\n§7(§eBeta§7)");
        $this->setNameTagAlwaysVisible();

        parent::initEntity($nbt);
    }
}