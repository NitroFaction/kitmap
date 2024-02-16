<?php

namespace Kitmap\item;

use Kitmap\Util;
use pocketmine\block\Water;
use pocketmine\item\Item as PmItem;
use pocketmine\item\StringToItemParser;
use pocketmine\item\ToolTier;
use pocketmine\item\VanillaItems;

class ExtraVanillaItems
{
    private static array $items = [];

    public function __construct()
    {
        self::addItem(VanillaItems::EXPERIENCE_BOTTLE(), new XpBottle());
        self::addItem(VanillaItems::FLINT_AND_STEEL(), new FlintAndSteal());
        self::addItem(VanillaItems::RAW_FISH(), new CombinedCookie());
        self::addItem(VanillaItems::COOKED_FISH(), new RegenerationCookie());
        self::addItem(VanillaItems::RAW_SALMON(), new SpeedCookie());
        self::addItem(VanillaItems::COOKED_SALMON(), new StrengthCookie());
        self::addItem(VanillaItems::NAUTILUS_SHELL(), new PotionLauncher());
        self::addItem(VanillaItems::SPLASH_POTION(), new SplashPotion());
        self::addItem(VanillaItems::ENDER_PEARL(), new EnderPearl());
        self::addItem(VanillaItems::STONE_SHOVEL(), new Fork());
        self::addItem(VanillaItems::STONE_HOE(), new WateringCan());
        self::addItem(VanillaItems::STONE_AXE(), new StoneAxe());

        self::addItem(VanillaItems::TURTLE_HELMET(), new Armor(334, 2));

        self::addItem(VanillaItems::GOLDEN_HELMET(), new Armor(1051, 3));
        self::addItem(VanillaItems::GOLDEN_CHESTPLATE(), new Armor(1531, 8));
        self::addItem(VanillaItems::GOLDEN_LEGGINGS(), new Armor(1434, 6));
        self::addItem(VanillaItems::GOLDEN_BOOTS(), new Armor(1243, 4));

        self::addItem(VanillaItems::NETHERITE_HELMET(), new Armor(1402, 4));
        self::addItem(VanillaItems::NETHERITE_CHESTPLATE(), new Armor(2042, 9));
        self::addItem(VanillaItems::NETHERITE_LEGGINGS(), new Armor(1912, 7));
        self::addItem(VanillaItems::NETHERITE_BOOTS(), new Armor(1658, 4));

        self::addItem(VanillaItems::DIAMOND_SWORD(), new Sword(VanillaItems::DIAMOND_SWORD()->getMaxDurability(), 7));
        self::addItem(VanillaItems::GOLDEN_SWORD(), new Sword(2286, 9));
        self::addItem(VanillaItems::NETHERITE_SWORD(), new Sword(3048, 10));

        new Craft();
    }

    public static function addItem(PmItem $item, Item $replace): void
    {
        self::$items[Util::reprocess($item->getVanillaName())] = $replace;
    }

    public static function getVanillaItemByItem(Item $item): PmItem
    {
        foreach (self::$items as $itemName => $replace) {
            if ($item instanceof $replace) {
                return StringToItemParser::getInstance()->parse($itemName) ?? VanillaItems::AIR();
            }
        }
        return VanillaItems::AIR();
    }

    public static function getItem(PmItem $item): Item
    {
        return self::$items[Util::reprocess($item->getVanillaName())] ?? new Item();
    }
}