<?php

namespace Kitmap\item;

use Kitmap\Main;
use Kitmap\Util;
use pocketmine\block\Beetroot;
use pocketmine\block\Block;
use pocketmine\block\Crops;
use pocketmine\block\Potato;
use pocketmine\block\Stem;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\Wheat;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\Item as PmItem;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

class Fork extends Durable
{
    public function onBreak(BlockBreakEvent $event): bool
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        if ($block instanceof Crops && !$block->ticksRandomly()) {
            $seed = $block->asItem();

            if ($player->getInventory()->contains($seed)) {
                $down = $block->getPosition()->subtract(0, 1, 0);

                Main::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player, $down, $block, $seed): void {
                    $block->getPosition()->getWorld()->useItemOn($down, $seed, 1, $down, $player);
                    $player->getInventory()->removeItem($seed);
                }), 1);

                $this->applyDamage($player);
            } else {
                $event->cancel();
                $player->sendMessage(Util::PREFIX . "Vous ne disposez pas des graines nécessaires pour replanter vos champs automatiquement !");
            }
        } else {
            $event->cancel();
        }

        return false;
    }

    public function onUse(PlayerItemUseEvent $event): bool
    {
        $player = $event->getPlayer();

        if ($player->isSneaking()) {
            $this->changeMode($player);
        }

        return false;
    }

    private function changeMode(Player $player): void
    {
        $mode = $this->getForkMode($player);

        if ($mode === 3) {
            $this->changeForkMode($player, 5);
            $player->sendMessage(Util::PREFIX . "Votre fourche vient de passer en §9mode 5x5 §fet elle perdra donc §93 points de durabilité §fpar utilisation !");
        } else {
            $this->changeForkMode($player, 3);
            $player->sendMessage(Util::PREFIX . "Votre fourche vient de passer en §9mode 3x3 §fet elle perdra donc §91 point de durabilité §fpar utilisation !");
        }
    }

    private function getForkMode(Player $player): int
    {
        $item = $player->getInventory()->getItemInHand();
        $value = $item->getNamedTag()->getTag("mode");

        if (is_null($value)) {
            $this->changeForkMode($player, 3, true);
            return 3;
        }

        return intval($value->getValue());
    }

    private function changeForkMode(Player $player, int $mode, bool $initialize = false): void
    {
        $item = $player->getInventory()->getItemInHand();
        $lores = $item->getLore();

        if ($initialize) {
            $lores = [
                "",
                "§o§7Sneak + Clique gauche pour changer de mode"
            ];
        }

        $lores[0] = "§o§7Mode " . $mode . "x" . $mode;
        $item->getNamedTag()->setInt("mode", $mode);

        $item->setLore($lores);
        $player->getInventory()->setItemInHand($item);
    }

    public function onInteract(PlayerInteractEvent $event): bool
    {
        $player = $event->getPlayer();
        $mode = $this->getForkMode($player);

        $block = $event->getBlock();
        $position = $block->getPosition();

        $event->cancel();

        if (($event->getAction() === $event::RIGHT_CLICK_BLOCK) || ($event->getAction() === $event::LEFT_CLICK_BLOCK && !$player->isSneaking())) {
            if ($block->hasSameTypeId(VanillaBlocks::DIRT()) || $block->hasSameTypeId(VanillaBlocks::GRASS()) || $block->hasSameTypeId(VanillaBlocks::FARMLAND())) {
                if ($position->getWorld()->getBlock($position->add(0, 1, 0))->hasSameTypeId(VanillaBlocks::AIR())) {
                    if ($mode === 3) {
                        $this->applyDamage($player);
                        $this->addBlockAround($block, 1);
                    } else {
                        $this->applyDamage($player, 3);
                        $this->addBlockAround($block, 2);
                    }
                }
            }
        } else if ($event->getAction() === $event::LEFT_CLICK_BLOCK && $player->isSneaking()) {
            $this->changeMode($player);
        }

        return false;
    }

    private function addBlockAround(Block $block, int $rayon): void
    {
        $position = $block->getPosition();
        $world = $position->getWorld();

        $minX = $position->getX() - $rayon;
        $maxX = $position->getX() + $rayon;
        $minZ = $position->getZ() - $rayon;
        $maxZ = $position->getZ() + $rayon;

        for ($x = $minX; $x <= $maxX; $x++) {
            for ($z = $minZ; $z <= $maxZ; $z++) {
                $y = $position->getY();
                $block = $world->getBlockAt($x, $y, $z);

                if ($block->hasSameTypeId(VanillaBlocks::DIRT()) || $block->hasSameTypeId(VanillaBlocks::GRASS())) {
                    if ($world->getBlock($position->add(0, 1, 0))->hasSameTypeId(VanillaBlocks::AIR())) {
                        $world->setBlock($block->getPosition(), VanillaBlocks::FARMLAND());
                    }
                }
            }
        }
    }

    private function getSeedByBlock(Crops $block): ?PmItem
    {
        return match (true) {
            $block instanceof Beetroot => VanillaItems::BEETROOT_SEEDS(),
            $block instanceof Potato => VanillaItems::POTATO(),
            $block instanceof Stem => VanillaItems::MELON_SEEDS(),
            $block instanceof Wheat => VanillaItems::WHEAT_SEEDS(),
            default => null
        };
    }

    public function getMaxDurability(): int
    {
        return 500;
    }
}