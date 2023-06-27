<?php

namespace Kitmap\entity\entities;

use Kitmap\command\player\Top;
use Kitmap\handler\Cache;
use Kitmap\handler\SkinAPI;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;

class TopEntity extends Human
{
    private int $tickToUpdate = 600;

    public function attack(EntityDamageEvent $source): void
    {
        $source->cancel();
    }

    protected function entityBaseTick(int $tickDiff = 1): bool
    {
        if ($this->isClosed()) {
            return false;
        }

        if ($this->isAlive()) {
            --$this->tickToUpdate;

            if ($this->tickToUpdate <= 0) {
                $this->setNameTag($this->getUpdate());
                $this->tickToUpdate = 600;
            }
        }
        return parent::entityBaseTick($tickDiff);
    }

    private function getUpdate(): string
    {
        $top = Cache::$config["top"];

        $position = $this->getLocation();
        $text = $position->getX() . ":" . $position->getY() . ":" . $position->getZ() . ":" . $position->getWorld()->getFolderName();

        $category = $top[$text] ?? false;

        if (is_bool($category)) {
            return "";
        }

        $list = Top::getPlayersTopList($category);

        $name = array_keys($list)[0];
        $skin = SkinAPI::getSkinFromName(strtolower($name));

        $this->setSkin($skin);
        $this->sendSkin();

        return match ($category) {
            "death" => "§e" . $name . "\nTop #1 Mort",
            "money" => "§e" . $name . "\nTop #1 Money",
            "played_time" => "§e" . $name . "\nTop #1 Nerd",
            default => "§e" . $name . "\nTop #1 Kill",
        };
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);
        $this->setScale(0.80);

        $this->setNameTag($this->getUpdate());
        $this->setNameTagAlwaysVisible();
    }
}