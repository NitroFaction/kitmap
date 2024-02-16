<?php

namespace Kitmap\block;

use pocketmine\block\Block as PmBlock;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\player\Player;

class Stone extends Block
{
    public function getDrops(PmBlock $block, Item $item, Player $player = null): ?array
    {
        if (mt_rand(0, 30) === 0) {
            return [VanillaBlocks::NETHER_WART()->asItem()];
        } else {
            return [];
        }
    }
}