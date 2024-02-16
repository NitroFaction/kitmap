<?php

namespace Kitmap\block;

use Kitmap\handler\Jobs;
use pocketmine\block\Block as PmBlock;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\world\sound\AmethystBlockChimeSound;

class NetherGoldOre extends Block
{
    public function getDropsMine(Player $player, PmBlock $block): ?array
    {
        $items = [
            VanillaItems::TURTLE_HELMET(),
            VanillaItems::COOKED_FISH(),
            VanillaItems::COOKED_SALMON(),
            VanillaItems::RAW_SALMON(),
            VanillaBlocks::REDSTONE()->asItem(),
            VanillaBlocks::CHISELED_NETHER_BRICKS()->asItem(),
            VanillaBlocks::SUNFLOWER()->asItem(),
            VanillaBlocks::FLOWERING_AZALEA_LEAVES()->asItem(),
            VanillaItems::EXPERIENCE_BOTTLE()->setCount(3),
            VanillaBlocks::LAPIS_LAZULI()->asItem(),
            VanillaItems::NAUTILUS_SHELL(),
            VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::BROWN())->asItem(),
            VanillaItems::CARROT(),
            VanillaItems::POTATO(),
            VanillaItems::BEETROOT(),
            VanillaItems::WHEAT_SEEDS(),
            VanillaItems::BEETROOT_SEEDS(),
            VanillaBlocks::STONE()->asItem(),
            VanillaBlocks::COBBLESTONE()->asItem(),
            VanillaBlocks::DIRT()->asItem(),
            VanillaBlocks::GLASS()->asItem(),
            VanillaItems::BAMBOO(),
            VanillaItems::MELON(),
            VanillaItems::SWEET_BERRIES()
        ];

        $player->broadcastSound(new AmethystBlockChimeSound());
        Jobs::addXp($player, "Mineur", 5, false);

        return [
            40,
            VanillaBlocks::BEDROCK(),
            $block,
            [$items[array_rand($items)]]
        ];
    }
}