<?php

namespace Kitmap\block;

use pocketmine\block\Block as PmBlock;
use pocketmine\block\CocoaBlock;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;

class CocoaPod extends Block
{
    public function getDropsMine(Player $player, PmBlock $block): ?array
    {
        $block = clone $block;

        $items = [
            VanillaItems::COOKED_FISH(),
            VanillaItems::COOKED_SALMON(),
            VanillaItems::RAW_SALMON()
        ];

        return [
            15,
            VanillaBlocks::AIR(),
            $block instanceof CocoaBlock ? $block->setAge($block::MAX_AGE) : $block,
            [$items[array_rand($items)]]
        ];
    }
}