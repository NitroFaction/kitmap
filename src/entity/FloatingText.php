<?php /** @noinspection PhpUnused */

namespace Kitmap\entity;

use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

abstract class FloatingText extends Living
{
    const DEFAULT_PERIOD = 20;

    protected bool $gravityEnabled = false;
    protected float $gravity = 0.0;

    protected ?int $period = self::DEFAULT_PERIOD;
    private int $tickToUpdate;

    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);
        $this->tickToUpdate = $this->getPeriod();

        $this->setNameTagAlwaysVisible();
        $this->setNameTag($this->getUpdate());

        $this->setScale(0.001);
        $this->setNoClientPredictions();
    }

    abstract protected function getPeriod(): ?int;

    abstract protected function getUpdate(): string;

    public static function getNetworkTypeId(): string
    {
        return EntityIds::CHICKEN;
    }

    public function getName(): string
    {
        return "Floating";
    }

    public function attack(EntityDamageEvent $source): void
    {
        $source->cancel();
    }

    public function knockBack(float $x, float $z, float $force = 0.4, ?float $verticalLimit = 0.4): void
    {
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.7, 0.4);
    }

    protected function entityBaseTick(int $tickDiff = 1): bool
    {
        if ($this->isClosed()) {
            return false;
        }

        if (is_int($this->getPeriod()) && $this->isAlive()) {
            --$this->tickToUpdate;

            if ($this->tickToUpdate <= 0) {
                $this->setNameTag($this->getUpdate());
                $this->tickToUpdate = $this->getPeriod();
            }
        }
        return parent::entityBaseTick($tickDiff);
    }
}