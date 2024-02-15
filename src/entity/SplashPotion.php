<?php

namespace Kitmap\entity;

use pocketmine\block\BlockTypeTags;
use pocketmine\block\VanillaBlocks;
use pocketmine\color\Color;
use pocketmine\entity\effect\InstantEffect;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\SplashPotion as PmSplashPotion;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\item\PotionType;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\world\particle\PotionSplashParticle;
use pocketmine\world\sound\PotionSplashSound;
use function count;
use function round;

class SplashPotion extends PmSplashPotion
{
    public const TAG_POTION_ID = "PotionId";

    protected float $gravity = 0.065;
    protected float $drag = 0.0025;

    public function __construct(Location $location, ?Entity $shootingEntity, PotionType $potionType, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $shootingEntity, $potionType, $nbt);
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        if ($this->isCollided) {
            $this->flagForDespawn();
        }
        return parent::entityBaseTick($tickDiff);
    }

    protected function onHit(ProjectileHitEvent $event): void
    {
        $effects = $this->getPotionEffects();
        $hasEffects = true;

        $owningentity = $event->getEntity()->getOwningEntity();

        if ($owningentity instanceof Player && $owningentity->isAlive()) {
            $event->getEntity()->teleport($owningentity->getPosition()->asPosition());
        }

        if (count($effects) !== 0) {
            $colors = [];

            foreach ($effects as $effect) {
                $level = $effect->getEffectLevel();

                for ($j = 0; $j < $level; ++$j) {
                    $colors[] = $effect->getColor();
                }
            }
            $particle = new PotionSplashParticle(Color::mix(...$colors));
        } else {
            $particle = new PotionSplashParticle(PotionSplashParticle::DEFAULT_COLOR());
            $hasEffects = false;
        }

        $this->getWorld()->addParticle($this->location, $particle);
        $this->broadcastSound(new PotionSplashSound());

        if ($hasEffects) {
            if (!$this->willLinger()) {
                foreach ($this->getWorld()->getNearbyEntities($this->boundingBox->expandedCopy(4.125, 2.125, 4.125), $this) as $entity) {
                    if ($entity instanceof Player and $entity->isAlive()) {
                        $distanceSquared = $entity->getEyePos()->distanceSquared($this->location);

                        if ($distanceSquared > 16) continue;
                        $distanceMultiplier = 1.45 - (sqrt($distanceSquared) / 4);

                        if ($event instanceof ProjectileHitEntityEvent and $entity === $event->getEntityHit()) {
                            $distanceMultiplier = 1.0;
                        }

                        foreach ($this->getPotionEffects() as $effect) {
                            if (!($effect->getType() instanceof InstantEffect)) {
                                $newDuration = (int)round($effect->getDuration() * 0.75 * $distanceMultiplier);
                                if ($newDuration < 20) continue;

                                $effect->setDuration($newDuration);
                                $entity->getEffects()->add($effect);
                            } else {
                                $effect->getType()->applyEffect($entity, $effect, $distanceMultiplier, $this);
                            }
                        }
                    }
                }
            }
        } else if ($event instanceof ProjectileHitBlockEvent && $this->getPotionType() === PotionType::WATER) {
            $blockIn = $event->getBlockHit()->getSide($event->getRayTraceResult()->getHitFace());

            if ($blockIn->hasTypeTag(BlockTypeTags::FIRE)) {
                $this->getWorld()->setBlock($blockIn->getPosition(), VanillaBlocks::AIR());
            }
            foreach ($blockIn->getHorizontalSides() as $horizontalSide) {
                if ($horizontalSide->hasTypeTag(BlockTypeTags::FIRE)) {
                    $this->getWorld()->setBlock($horizontalSide->getPosition(), VanillaBlocks::AIR());
                }
            }
        }
    }
}