<?php

namespace Kitmap\entity;

use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class LightningBolt extends Entity
{
    protected int $age = 0;

    public static function getNetworkTypeId(): string
    {
        return EntityIds::LIGHTNING_BOLT;
    }

    protected function entityBaseTick(int $tickDiff = 1): bool
    {
        if ($this->closed) return false;
        $this->age += $tickDiff;
        if ($this->age > 20) $this->flagForDespawn();
        return parent::entityBaseTick($tickDiff);
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(1.8, 0.3);
    }

    protected function getInitialDragMultiplier(): float
    {
        return 0.0;
    }

    protected function getInitialGravity(): float
    {
        return 0.0;
    }
}
