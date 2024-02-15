<?php

namespace Kitmap\block;

use Kitmap\command\player\Enchant;
use Kitmap\Util;
use pocketmine\event\player\PlayerInteractEvent;

class EnchantingTable extends Block
{
    public function onInteract(PlayerInteractEvent $event): bool
    {
        $player = $event->getPlayer();

        if (!$player->isSneaking() && $event->getAction() === $event::RIGHT_CLICK_BLOCK) {
            Util::removeCurrentWindow($player);
            Enchant::openEnchantTable($player, false);

            $event->cancel();
            return true;
        }
        return false;
    }
}