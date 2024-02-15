<?php

namespace Kitmap\item;

use pocketmine\event\player\PlayerInteractEvent;

class FlintAndSteal extends Item
{
    public function onInteract(PlayerInteractEvent $event): bool
    {
        $event->cancel();
        return true;
    }
}