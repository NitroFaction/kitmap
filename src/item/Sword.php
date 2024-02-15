<?php

namespace Kitmap\item;

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\player\Player;

class Sword extends Durable
{
    public function __construct(private readonly int $maxDurability = -1, private readonly int $attackPoints = -1)
    {
    }

    public function onAttack(EntityDamageEvent $event, Player $player): bool
    {
        if ($this->attackPoints > 0) {
            $event->setBaseDamage($this->getAttackPoints());
        }

        return false;
    }

    public function getAttackPoints(): int
    {
        return $this->attackPoints;
    }

    public function getMaxDurability(): int
    {
        return $this->maxDurability;
    }
}