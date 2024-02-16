<?php

namespace Kitmap\block;

use pocketmine\block\Block as PmBlock;
use pocketmine\block\NetherWartPlant as PmNetherWartPlant;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;

class NetherWartPlant extends Block
{
    public function getDrops(PmBlock $block, Item $item, Player $player = null): ?array
    {
        if (
            $block instanceof PmNetherWartPlant &&
            $block->getAge() === $block::MAX_AGE &&
            mt_rand(0, 30) === 0
        ) {
            return [VanillaItems::RABBIT_FOOT()];
        } else {
            if (mt_rand(0, 20) === 0) {
                return [VanillaBlocks::NETHER_WART()->asItem()];
            } else {
                return [];
            }
        }
    }

    public function getXpDropAmount(): ?int
    {
        return mt_rand(1, 3);
    }
}