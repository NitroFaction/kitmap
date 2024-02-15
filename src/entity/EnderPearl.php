<?php

namespace Kitmap\entity;

use Kitmap\Session;
use Kitmap\Util;
use pocketmine\block\Block;
use pocketmine\block\PressurePlate;
use pocketmine\block\Tripwire;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\projectile\EnderPearl as PmEnderPearl;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class EnderPearl extends PmEnderPearl
{
    protected function onHit(ProjectileHitEvent $event): void
    {
        $owner = $this->getOwningEntity();

        if ($owner !== null) {
            if ($owner->getWorld() !== $this->getWorld()) {
                return;
            }

            parent::onHit($event);
        }
    }

    protected function calculateInterceptWithBlock(Block $block, Vector3 $start, Vector3 $end): ?RayTraceResult
    {
        $player = $this->getOwningEntity();

        if ($player instanceof Player && ($block->isSameState(VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::BROWN())) || $block->hasSameTypeId(VanillaBlocks::REDSTONE()))) {
            $player->sendMessage(Util::PREFIX . "Votre perle a été annulé car elle a touché un bloc antiback, votre cooldown perle à été reset à §92 §fsecondes");
            Session::get($player)->setCooldown("enderpearl", 2);

            $this->flagForDespawn();
            return null;
        }

        if ($block instanceof PressurePlate || $block instanceof Tripwire) {
            $position = $block->getPosition();
            $bb = new AxisAlignedBB($position->getX(), $position->getY(), $position->getZ(), $position->getX(), $position->getY(), $position->getZ());

            return new RayTraceResult($bb, Facing::UP, $block->getPosition());
        } else {
            return $block->calculateIntercept($start, $end);
        }
    }
}