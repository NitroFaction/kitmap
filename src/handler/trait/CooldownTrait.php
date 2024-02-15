<?php

namespace Kitmap\handler\trait;

use pocketmine\player\Player;

trait CooldownTrait
{
    private array $cooldowns = [];

    public function inCooldown(Player $player): bool
    {
        return isset($this->cooldowns[$player->getXuid()]) && $this->cooldowns[$player->getXuid()] > microtime(true);
    }

    public function setCooldown(Player $player, float $time): void
    {
        $this->cooldowns[$player->getXuid()] = microtime(true) + $time;
    }
}