<?php

namespace Kitmap\block;

use Kitmap\Util;
use pocketmine\block\Block as PmBlock;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\player\PlayerJumpEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class Elevator extends Block
{
    public function onJump(PlayerJumpEvent $event): bool
    {
        $player = $event->getPlayer();

        $x = $player->getPosition()->getFloorX();
        $y = $player->getPosition()->getFloorY() - 1;
        $z = $player->getPosition()->getFloorZ();

        $block = $player->getPosition()->getWorld()->getBlockAt($x, $y, $z);

        if (!$this->getTwoBlocksAvaible($block)) {
            $player->sendMessage(Util::PREFIX . "L'elevateur ou vous êtes est inutilisable");
            return false;
        }

        $found = false;
        $y++;

        for (; $y <= World::Y_MAX; $y++) {
            if (($found = $this->isElevatorBlock($x, $y, $z, $player->getWorld())) instanceof PmBlock) {
                break;
            }
        }

        if ($found instanceof PmBlock) {
            if (!$this->getTwoBlocksAvaible($found)) {
                $player->sendMessage(Util::PREFIX . "L'elevateur au dessus est inutilisable");
                return false;
            }

            $player->teleport(new Vector3($x + 0.5, $y + 1, $z + 0.5));
        } else {
            $player->sendMessage(Util::PREFIX . "Aucun elevateur au dessus");
        }

        return false;
    }

    public function onSneak(PlayerToggleSneakEvent $event): bool
    {
        $player = $event->getPlayer();

        $x = $player->getPosition()->getFloorX();
        $y = $player->getPosition()->getFloorY() - 1;
        $z = $player->getPosition()->getFloorZ();

        $block = $player->getPosition()->getWorld()->getBlockAt($x, $y, $z);

        if (!$this->getTwoBlocksAvaible($block)) {
            $player->sendMessage(Util::PREFIX . "L'elevateur ou vous êtes est inutilisable");
            return false;
        }

        $found = false;
        $y--;

        for (; $y >= World::Y_MIN; $y--) {
            if (($found = $this->isElevatorBlock($x, $y, $z, $player->getWorld())) instanceof PmBlock) {
                break;
            }
        }

        if ($found instanceof PmBlock) {
            if (!$this->getTwoBlocksAvaible($found)) {
                $player->sendMessage(Util::PREFIX . "L'elevateur au dessus est inutilisable");
                return false;
            }

            $player->teleport(new Vector3($x + 0.5, $y + 1, $z + 0.5));
        } else {
            $player->sendMessage(Util::PREFIX . "Aucun elevateur en dessous");
        }

        return false;
    }

    private function getTwoBlocksAvaible(PmBlock $block): bool
    {
        $position = $block->getPosition();

        $block1 = $position->getWorld()->getBlock($position->add(0, 1, 0));
        $block2 = $position->getWorld()->getBlock($position->add(0, 2, 0));

        return $block1->hasSameTypeId(VanillaBlocks::AIR()) && $block2->hasSameTypeId(VanillaBlocks::AIR());
    }

    public static function isElevatorBlock(int $x, int $y, int $z, World $world): ?PmBlock
    {
        $block = $world->getBlockAt($x, $y, $z);
        return $block->hasSameTypeId(VanillaBlocks::LAPIS_LAZULI()) ? $block : null;
    }
}