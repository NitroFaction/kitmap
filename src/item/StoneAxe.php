<?php

namespace Kitmap\item;

use Kitmap\command\staff\op\AddClaims;
use Kitmap\Util;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\permission\DefaultPermissions;

class StoneAxe extends Item
{
    public function onBreak(BlockBreakEvent $event): bool
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        if (!is_null($player->getInventory()->getItemInHand()->getNamedTag()->getTag("claims"))) {
            if (
                AddClaims::addClaim($block->getPosition()->getX(), $block->getPosition()->getZ()) &&
                $player->hasPermission(DefaultPermissions::ROOT_OPERATOR)
            ) {
                $player->sendMessage(Util::PREFIX . "Chunk ajouté");
            }

            $event->cancel();
        }

        return true;
    }
}