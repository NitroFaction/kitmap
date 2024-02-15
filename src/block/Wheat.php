<?php

namespace Kitmap\block;

use Kitmap\Session;
use pocketmine\block\Block as PmBlock;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\Wheat as PmWheat;
use pocketmine\player\Player;

class Wheat extends Block
{
    public function getDropsMine(Player $player, PmBlock $block): ?array
    {
        $block = clone $block;

        Session::get($player)->addValue("money", ($rand = mt_rand(1, 10)));
        $player->sendTip("+ §9" . $rand . " §fPièces §9+");

        return [
            15,
            VanillaBlocks::AIR(),
            $block instanceof PmWheat ? $block->setAge($block::MAX_AGE) : $block,
            []
        ];
    }
}