<?php

namespace Kitmap\entity;

use Kitmap\handler\Faction;
use Kitmap\Main;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\object\FallingBlock;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\Position;
use pocketmine\world\sound\BlockBreakSound;

class GhostBlock extends FallingBlock
{
    public function attack(EntityDamageEvent $source): void
    {
        if ($source instanceof EntityDamageByEntityEvent) {
            $cause = $source->getCause();
            $damager = $source->getDamager();

            if ($cause === $source::CAUSE_ENTITY_ATTACK && $damager instanceof Player) {
                $block = $this->getBlock();
                $position = new Position($this->getPosition()->getFloorX(), $this->getPosition()->getFloorY(), $this->getPosition()->getFloorZ(), $this->getWorld());

                if (!Faction::canBuild($damager, $position, "break") || $damager->getGamemode() === GameMode::SPECTATOR() || $damager->getGamemode() === GameMode::ADVENTURE()) {
                    return;
                }

                $this->setInvisible();
                $this->flagForDespawn();

                $world = $this->getPosition()->getWorld();

                $world->addParticle($this->getPosition(), new BlockBreakParticle($block));
                $world->addSound($this->getPosition(), new BlockBreakSound($block));

                if ($damager->getGamemode() === GameMode::SURVIVAL()) {
                    $world->dropItem($this->getPosition(), VanillaBlocks::CHISELED_NETHER_BRICKS()->asItem());
                }
            }
        }

        $source->cancel();
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        $this->setNoClientPredictions();
        $this->setInvisible(false);

        $this->gravityEnabled = false;
        $this->onGround = true;
        $this->networkPropertiesDirty = true;

        parent::initEntity($nbt);

        if ($this->getWorld() === Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()) {
            $this->flagForDespawn();
            $this->getWorld()->dropItem($this->getPosition(), VanillaBlocks::CHISELED_NETHER_BRICKS()->asItem());
        }
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(1, 1);
    }

    protected function entityBaseTick(int $tickDiff = 1): bool
    {
        return true;
    }
}