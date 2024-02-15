<?php

namespace Kitmap\block;

use Kitmap\entity\GhostBlock as GhostBlockEntity;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\block\Block as PmBlock;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Location;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\scheduler\ClosureTask;

class GhostBlock extends Block
{
    public function onPlace(BlockPlaceEvent $event): bool
    {
        $blockAgainst = $event->getBlockAgainst();

        if ($blockAgainst->getPosition()->getWorld() === Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()) {
            $event->getPlayer()->sendMessage(Util::PREFIX . "Les ghostblock ne peuvent pas être posés dans le monde avec les APS");
            return true;
        }

        foreach ($event->getTransaction()->getBlocks() as [$x, $y, $z, $block]) {
            if (!$block instanceof PmBlock) {
                continue;
            }

            Main::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($block, $blockAgainst) {
                $position = $block->getPosition();

                $fall = new GhostBlockEntity(Location::fromObject($position->add(0.5, 0.5, 0.5), $position->getWorld()), $blockAgainst);
                $fall->spawnToAll();

                $position->getWorld()->setBlock($position, VanillaBlocks::AIR());
            }), 1);
        }

        return true;
    }
}