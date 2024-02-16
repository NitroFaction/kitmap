<?php

namespace Kitmap\entity;

use Kitmap\Main;
use Kitmap\Util;
use pocketmine\block\VanillaBlocks;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\GameMode;
use pocketmine\player\Player as PmPlayer;
use pocketmine\world\Position;
use pocketmine\world\World;

class Player extends PmPlayer
{
    private array $lastPositions = [];
    private int $bedrockTicks = 0;

    protected function entityBaseTick(int $tickDiff = 1): bool
    {
        $tick = parent::entityBaseTick($tickDiff);
        $gamemode = $this->getGamemode();

        if ($gamemode === GameMode::CREATIVE() && !$this->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            $this->setGamemode(GameMode::SURVIVAL());
        }

        $this->getHungerManager()->setFood(18);

        if ($this->bedrockTicks === 0 && $gamemode === GameMode::SURVIVAL()) {
            $this->lastPositions[] = $this->getPosition();

            if (count($this->lastPositions) > 20) {
                array_shift($this->lastPositions);
            }
        }

        foreach ($this->getBlocksIntersected(0.001) as $block) {
            if ($gamemode === GameMode::SURVIVAL() && $block->hasSameTypeId(VanillaBlocks::BEDROCK())) {
                $this->bedrockTicks++;

                if ($this->bedrockTicks > 4) {
                    array_pop($this->lastPositions);
                    $last = array_pop($this->lastPositions);

                    if ($last instanceof Position && $last->isValid()) {
                        $this->bedrockTicks = 0;

                        $this->sendMessage(Util::PREFIX . "Vous n'avez pas le droit de suffoquer dans la bedrock, vous avez été téléporté à votre derniere position");
                        $this->teleport($last);
                    }
                }

                break;
            } else if (!$block->hasSameTypeId(VanillaBlocks::BEDROCK())) {
                $this->bedrockTicks = 0;
            }

            if ($block->getName() === "End Portal") {
                $world = Main::getInstance()->getServer()->getWorldManager()->getWorldByName("mine");

                if (!$world instanceof World) {
                    break;
                }

                if (Util::insideZone($block->getPosition(), "blocks-tp")) {
                    $this->teleport(new Position(50013.5, 12.5, 89, $world));
                } else if (Util::insideZone($block->getPosition(), "mine-tp")) {
                    $this->teleport($world->getSpawnLocation());
                }

                break;
            }
        }

        return $tick;
    }
}