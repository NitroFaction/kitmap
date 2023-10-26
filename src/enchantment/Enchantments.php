<?php

namespace Kitmap\enchantment;

use Kitmap\enchantment\childs\sword\Ares;
use Kitmap\enchantment\childs\sword\LightningStrike;
use Kitmap\enchantment\childs\sword\Looter;
use Kitmap\enchantment\childs\util\Glow;
use pocketmine\data\bedrock\EnchantmentIdMap;

class Enchantments
{

    public function __construct()
    {
        $enchantmentIpMap = EnchantmentIdMap::getInstance();

        // FAKE ENCHANT
        $enchantmentIpMap->register(EnchantmentIds::GLOW, new Glow());

        $enchantmentIpMap->register(EnchantmentIds::LOOTER, new Looter());
        $enchantmentIpMap->register(EnchantmentIds::LIGHTNING_STRIKE, new LightningStrike());
        $enchantmentIpMap->register(EnchantmentIds::ARES, new Ares());
    }

}
