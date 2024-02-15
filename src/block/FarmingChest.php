<?php

namespace Kitmap\block;

use Kitmap\handler\Jobs;
use Kitmap\Util;
use pocketmine\block\Beetroot;
use pocketmine\block\Block as PmBlock;
use pocketmine\block\Carrot;
use pocketmine\block\Potato;
use pocketmine\block\tile\Chest;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\Wheat;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;

class FarmingChest extends Block
{
    public function onInteract(PlayerInteractEvent $event): bool
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        if ($event->getAction() !== $event::RIGHT_CLICK_BLOCK) {
            return false;
        } else if ($this->inCooldown($player)) {
            return true;
        }

        $this->setCooldown($player, 0.50);

        if ($player->isSneaking()) {
            $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas utiliser de farming chest en sneak");
            return true;
        }

        $this->addBlock($player, $block);

        return true;
    }

    private function addBlock(Player $player, PmBlock $block): void
    {
        $position = $block->getPosition();
        $world = $position->getWorld();

        $tile = $world->getTile($position);
        $y = $position->getY();

        if (!$tile instanceof Chest) {
            return;
        }

        $minX = $position->getX() - 8;
        $maxX = $position->getX() + 8;
        $minZ = $position->getZ() - 8;
        $maxZ = $position->getZ() + 8;

        $results = [];

        for ($x = $minX; $x <= $maxX; $x++) {
            for ($z = $minZ; $z <= $maxZ; $z++) {
                $crops = $world->getBlockAt($x, $y, $z);
                $seed = $this->getSeedByBlock($crops);

                if (!is_null($seed) && !$crops->ticksRandomly()) {
                    $tile->getInventory()->addItem($seed[0]->setCount($seed[1]));
                    $world->setBlock($crops->getPosition(), $block->asItem()->getBlock());

                    $results[$seed[2]] = ($results[$seed[2]] ?? 0) + $seed[1];
                }
            }
        }

        if ($results === []) {
            $player->sendMessage(Util::PREFIX . "Votre farming chest n'a trouvé aucune culture prête à être récoltée aux alentours");
            return;
        }


        Jobs::addXp($player, "Farmeur", min(1, intval(mt_rand(1, intval(array_sum($results)))) / 5));
        $player->sendMessage(Util::PREFIX . "Vous venez de récolter : §9" . $results[0] ?? 0 . " blé(s), " . $results[1] ?? 0 . " patate(s), " . $results[2] ?? 0 . " carotte(s) et " . $results[3] ?? 0 . " betterave(s) §fgrâce à votre farming chest !");
    }

    private function getSeedByBlock(PmBlock $block): ?array
    {
        return match (true) {
            $block instanceof Wheat => [VanillaItems::WHEAT_SEEDS(), 1, 0],
            $block instanceof Potato => [VanillaItems::POTATO(), mt_rand(1, 2), 1],
            $block instanceof Carrot => [VanillaItems::CARROT(), mt_rand(1, 2), 2],
            $block instanceof Beetroot => [VanillaItems::BEETROOT_SEEDS(), 1, 3],
            default => null
        };
    }

    public function getDrops(PmBlock $block, Item $item, Player $player = null): ?array
    {
        return [
            VanillaItems::EMERALD()->setCount(mt_rand(1, 4)),
            VanillaBlocks::EMERALD()->asItem()->setCount(mt_rand(1, 2))
        ];
    }
}