<?php

namespace Kitmap\item;

use Kitmap\handler\trait\CooldownTrait;
use Kitmap\Util;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\Item as PmItem;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\world\World;

class IrisGlove extends Durable
{
    use CooldownTrait;

    private array $modes = [
        "§9Labourage",
        "§9Plantage"
    ];

    public function getMaxDurability(): int
    {
        return 1500;
    }

    public function onInteract(PlayerInteractEvent $event): bool
    {
        $event->cancel();

        if ($event->getAction() !== $event::RIGHT_CLICK_BLOCK) {
            return true;
        }

        $player = $event->getPlayer();
        $item = $player->getInventory()->getItemInHand();

        if ($this->inCooldown($player)) {
            return true;
        } else {
            $this->setCooldown($player, 0.50);
        }

        $blockClicked = $event->getBlock();

        $lore = $item->getLore()[2] ?? null;
        $mode = array_search($lore, $this->modes);

        if (is_null($lore)) {
            $this->changeMode($item, $player, $this->modes[0]);
            $mode = 0;
        }

        $minX = $blockClicked->getPosition()->getX() - 2;
        $maxX = $blockClicked->getPosition()->getX() + 2;

        $minZ = $blockClicked->getPosition()->getZ() - 2;
        $maxZ = $blockClicked->getPosition()->getZ() + 2;

        $damage = 0;

        for ($x = $minX; $x <= $maxX; $x++) {
            for ($z = $minZ; $z <= $maxZ; $z++) {
                $position = new Position($x, $blockClicked->getPosition()->getY(), $z, $blockClicked->getPosition()->getWorld());
                $block = $blockClicked->getPosition()->getWorld()->getBlock($position);

                if ($mode === 0) {
                    if ($block->hasSameTypeId(VanillaBlocks::DIRT()) && $position->getWorld()->getBlock($position->add(0, 1, 0))->hasSameTypeId(VanillaBlocks::AIR())) {
                        if ($player->canInteract($position, 13) && World::Y_MAX > $position->getY()) {
                            $position->getWorld()->setBlock($position, VanillaBlocks::FARMLAND());
                            $damage++;
                        }
                    }
                } else if ($mode === 1) {
                    $i = 0;

                    $seeds = [
                        [VanillaItems::WHEAT(), VanillaBlocks::WHEAT()],
                        [VanillaItems::BEETROOT_SEEDS(), VanillaBlocks::BEETROOTS()],
                        [VanillaItems::POTATO(), VanillaBlocks::POTATOES()],
                        [VanillaItems::CARROT(), VanillaBlocks::CARROTS()]
                    ];

                    if (!$block->hasSameTypeId(VanillaBlocks::FARMLAND())) {
                        continue;
                    }

                    $vec = $position->add(0, 1, 0);

                    if (!$position->getWorld()->getBlock($vec)->hasSameTypeId(VanillaBlocks::AIR()) || !$player->canInteract($vec, 13) || $position->getY() > World::Y_MAX) {
                        continue;
                    }

                    foreach ($seeds as $arr) {
                        $count = Util::getItemCount($player, $arr[0]);

                        if ($count > 0) {
                            $position->getWorld()->setBlock($vec, $arr[1]);
                            $player->getInventory()->removeItem($arr[0]);

                            $damage++;
                            $i++;
                        }
                    }

                    if ($i === 0) {
                        $player->sendMessage(Util::PREFIX . "Vous ne possèdez aucune graine plantable dans votre inventaire");
                        goto skip;
                    }
                }
            }
        }

        if ($damage > 0) {
            $this->applyDamage($player, $damage);
        }

        skip:
        return true;
    }

    private function changeMode(PmItem $item, Player $player, $mode = "§9Plantage"): void
    {
        $lore = [
            " ",
            "§r§fMode:",
            $mode,
            "  ",
            "§r§fLe mode §9plantage §fsert à planter des graines",
            "§r§fLe mode §9labourage §fsert à labourer la terre",
            "   ",
            "§r§fSneak + Clique droit pour changer de mode",
            "§r§fLe rayon est de §95x5 §fblocs"
        ];

        $item = $item->setLore($lore);
        $player->getInventory()->setItemInHand($item);
    }

    public function onUse(PlayerItemUseEvent $event): bool
    {
        $player = $event->getPlayer();
        $item = $player->getInventory()->getItemInHand();

        $lore = $item->getLore()[2] ?? null;

        if ($player->isSneaking() || is_null($lore)) {
            if (is_null($lore)) {
                $lore = $this->modes[1];
            }

            if ($lore === $this->modes[1]) {
                $this->changeMode($item, $player, $this->modes[0]);
            } else if ($lore === $this->modes[0]) {
                $this->changeMode($item, $player, $this->modes[1]);
            }

            $player->sendMessage(Util::PREFIX . "Votre gant en iris vient de passer en mode: " . $item->getLore()[2]);
        }
        return true;
    }
}