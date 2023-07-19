<?php

namespace Kitmap\entity;

use Kitmap\command\staff\op\Elevator;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;

class ElevatorPhantom extends Living
{
    protected bool $gravityEnabled = false;
    protected float $gravity = 0.0;

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $this->setNameTagAlwaysVisible();
        $this->setNameTag("Elevateur");
        $this->setNoClientPredictions();
    }

    public function attack(EntityDamageEvent $source): void
    {
        $source->cancel();

        if ($source instanceof EntityDamageByEntityEvent) {
            $damager = $source->getDamager();

            if ($damager instanceof Player) {
                Elevator::openForm($damager);
            }
        }
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.3, 0.9);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::PHANTOM;
    }

    public function getName(): string
    {
        return "Elevateur";
    }
}