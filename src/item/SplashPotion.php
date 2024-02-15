<?php

namespace Kitmap\item;

use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\SplashPotion as PmSplashPotion;

class SplashPotion extends Item
{
    public function onUse(PlayerItemUseEvent $event): bool
    {
        $player = $event->getPlayer();

        $directionVector = $event->getDirectionVector();
        $item = $event->getItem();

        $event->cancel();

        if ($item instanceof PmSplashPotion) {
            $succes = $this->createPotion($directionVector, $player, $item->getType());

            if ($succes) {
                $this->projectileSucces($player, $item);
            }
        }

        return false;
    }
}