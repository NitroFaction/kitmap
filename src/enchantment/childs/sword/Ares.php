<?php /* @noinspection PhpDeprecationInspection */

namespace Kitmap\enchantment\childs\sword;

use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\ItemFlags;
use pocketmine\item\enchantment\Rarity;
use pocketmine\lang\Translatable;

class Ares extends Enchantment
{

    public function __construct()
    {
        parent::__construct(
            $this->getName(),
            $this->getRarity(),
            $this->getPrimaryItemFlags(),
            $this->getSecondaryItemFlags(),
            $this->getMaxLevel(),
        );
    }

    public function getName(): Translatable|string
    {
        return "Arès";
    }

    public function getRarity(): int
    {
        return Rarity::UNCOMMON;
    }

    public function getPrimaryItemFlags(): int
    {
        return ItemFlags::SWORD;
    }

    public function getSecondaryItemFlags(): int
    {
        return ItemFlags::NONE;
    }

    public function getMaxLevel(): int
    {
        return 1;
    }

}
