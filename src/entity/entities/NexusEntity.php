<?php

namespace Kitmap\entity\entities;

use Kitmap\entity\EntityManager;
use Kitmap\Main;
use pocketmine\data\bedrock\EntityLegacyIds;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use pocketmine\world\sound\ExplodeSound;
use skymin\bossbar\BossBarAPI;

class NexusEntity extends Living
{
    public const NETWORK_ID = EntityLegacyIds::ENDER_CRYSTAL;

    public int $height = 2;
    public float $width = 0.98;

    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);

        $this->setScale(2);
        $this->updateNameTag();
    }

    private function updateNameTag(): void
    {
        $this->setNameTag(floor($this->getHealth()) . " §c❤");
        $this->setNameTagAlwaysVisible();
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::ENDER_CRYSTAL;
    }

    public function spawnToAll(): void
    {
        parent::spawnToAll();
        $this->updateNameTag();
    }

    public function getName(): string
    {
        return "NexusEntity";
    }

    public function attack(EntityDamageEvent $source): void
    {
        if ($source instanceof EntityDamageByEntityEvent) {
            $damager = $source->getDamager();

            if ($damager instanceof Player) {
                $source->cancel();
                $updatedHp = $this->getHealth() - 1;

                if ($updatedHp % 20 == 0) {
                    foreach (Main::getInstance()->getServer()->getOnlinePlayers() as $player) {
                        $remaining = ($this->getHealth() / $this->getMaxHealth());
                        $percentage = $remaining;

                        BossBarAPI::getInstance()->sendBossBar(
                            $player,
                            "Nexus | Vie restante " . round($this->getHealth()),
                            2,
                            $percentage
                        );
                    }
                }

                if ($updatedHp > 0) {
                    $this->setHealth($updatedHp);
                    $this->updateNameTag();

                    $damager->broadcastSound(new ExplodeSound());
                    return;
                }

                foreach (Main::getInstance()->getServer()->getOnlinePlayers() as $player) {
                    BossBarAPI::getInstance()->hideBossBar($player, 2);
                }

                $this->close();
                EntityManager::dropItems($this->getPosition());
            }
        }
    }

    public function getMaxHealth(): int
    {
        return 10000;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(1.8, 0.6, 1.62);
    }
}