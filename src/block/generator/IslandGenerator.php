<?php

namespace Kitmap\block\generator;

use pocketmine\block\VanillaBlocks;
use pocketmine\world\ChunkManager;
use pocketmine\world\generator\Generator;

abstract class IslandGenerator extends Generator
{
    private array $config;

    public function __construct(int $seed, string $preset)
    {
        parent::__construct($seed, $preset);
        $this->config = json_decode($preset, true, flags: JSON_THROW_ON_ERROR);
    }

    public function generateChunk(ChunkManager $world, int $chunkX, int $chunkZ): void
    {
        if ($chunkX == 16 && $chunkZ == 16) {
            $this->generateChunkBlocks($world);
        }
    }

    private function generateChunkBlocks(ChunkManager $world): void
    {
        foreach ($this->config as $data) {
            [$x, $y, $z, $blockType] = explode(":", $data);
            $world->setBlockAt(intval($x), intval($y), intval($z), VanillaBlocks::$blockType());
        }
    }

    public function populateChunk(ChunkManager $world, int $chunkX, int $chunkZ): void
    {
    }
}