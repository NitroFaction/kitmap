<?php

namespace Kitmap\block;

use pocketmine\block\Block as PmBlock;
use pocketmine\block\VanillaBlocks;
use pocketmine\world\format\Chunk;

class ExtraVanillaBlocks
{
    private static array $blocks = [];

    public function __construct()
    {
        self::addBlock(VanillaBlocks::ANVIL(), new Anvil());
        self::addBlock(VanillaBlocks::COCOA_POD(), new CocoaPod());
        self::addBlock(VanillaBlocks::DEEPSLATE_EMERALD_ORE(), new DeepslateEmeraldOre());
        self::addBlock(VanillaBlocks::ENCHANTING_TABLE(), new EnchantingTable());
        self::addBlock(VanillaBlocks::ENDER_CHEST(), new Enderchest());
        self::addBlock(VanillaBlocks::TRAPPED_CHEST(), new FarmingChest());
        self::addBlock(VanillaBlocks::NETHER_GOLD_ORE(), new NetherGoldOre());
        self::addBlock(VanillaBlocks::NETHER_WART(), new NetherWartPlant());
        self::addBlock(VanillaBlocks::WHEAT(), new Wheat());
        self::addBlock(VanillaBlocks::CHISELED_NETHER_BRICKS(), new GhostBlock());
        self::addBlock(VanillaBlocks::NETHER_QUARTZ_ORE(), new ChunkBuster());

        new World();
    }

    public static function addBlock(PmBlock $block, Block $replace): void
    {
        self::$blocks[$block->getTypeId()] = $replace;
    }

    public static function getBlock(PmBlock $block): Block
    {
        return self::$blocks[$block->getTypeId()] ?? new Block();
    }
}