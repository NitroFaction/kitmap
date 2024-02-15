<?php

namespace Kitmap\block;

use Kitmap\handler\trait\CooldownTrait;
use pocketmine\block\Block as PmBlock;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\player\Player;

class Block
{
    use CooldownTrait;

    // False = return in the event
    // True = return in the event
    // Cancel in the function not automatic

    public function onInteract(PlayerInteractEvent $event): bool
    {
        return false;
    }

    public function onPlace(BlockPlaceEvent $event): bool
    {
        return false;
    }

    public function onBreak(BlockBreakEvent $event): bool
    {
        $drops = $this->getDrops($event->getBlock(), $event->getItem(), $event->getPlayer());
        $xp = $this->getXpDropAmount();

        if (!is_null($drops)) $event->setDrops($drops);
        if (!is_null($xp)) $event->setXpDropAmount($xp);

        return false;
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function getDrops(PmBlock $block, Item $item, Player $player = null): ?array
    {
        return null;
    }

    public function getXpDropAmount(): ?int
    {
        return null;
    }

    public function getDropsMine(Player $player, PmBlock $block): ?array
    {
        return null;
    }
}