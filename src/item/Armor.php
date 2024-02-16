<?php

namespace Kitmap\item;

use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Armor as PmArmor;

class Armor extends Durable
{
    public function __construct(private readonly int $maxDurability = 0, private readonly int $defensePoints = 0)
    {
    }

    public static function applyDamageModifiers(EntityDamageEvent $source, Living $entity): void
    {
        $source->setModifier(0, EntityDamageEvent::MODIFIER_ARMOR);
        $source->setModifier(0, EntityDamageEvent::MODIFIER_RESISTANCE);
        $source->setModifier(0, EntityDamageEvent::MODIFIER_ARMOR_ENCHANTMENTS);
        $source->setModifier(0, EntityDamageEvent::MODIFIER_ABSORPTION);
        $source->setModifier(0, EntityDamageEvent::MODIFIER_ARMOR_HELMET);

        if ($source->canBeReducedByArmor()) {
            //MCPE uses the same system as PC did pre-1.9
            $source->setModifier(-$source->getFinalDamage() * self::getArmorPoints($entity) * 0.04, EntityDamageEvent::MODIFIER_ARMOR);
        }

        $cause = $source->getCause();

        if (($resistance = $entity->getEffects()->get(VanillaEffects::RESISTANCE())) !== null && $cause !== EntityDamageEvent::CAUSE_VOID && $cause !== EntityDamageEvent::CAUSE_SUICIDE) {
            $source->setModifier(-$source->getFinalDamage() * min(1, 0.2 * $resistance->getEffectLevel()), EntityDamageEvent::MODIFIER_RESISTANCE);
        }

        $totalEpf = 0;

        foreach ($entity->getArmorInventory()->getContents() as $item) {
            if ($item instanceof PmArmor) {
                $totalEpf += $item->getEnchantmentProtectionFactor($source);
            }
        }

        $source->setModifier(-$source->getFinalDamage() * min(ceil(min($totalEpf, 25) * (mt_rand(50, 100) / 100)), 20) * 0.04, EntityDamageEvent::MODIFIER_ARMOR_ENCHANTMENTS);
        $source->setModifier(-min($entity->getAbsorption(), $source->getFinalDamage()), EntityDamageEvent::MODIFIER_ABSORPTION);

        if ($cause === EntityDamageEvent::CAUSE_FALLING_BLOCK && $entity->getArmorInventory()->getHelmet() instanceof Armor) {
            $source->setModifier(-($source->getFinalDamage() / 4), EntityDamageEvent::MODIFIER_ARMOR_HELMET);
        }
    }

    private static function getArmorPoints(Living $player): int
    {
        $total = 0;

        foreach ($player->getArmorInventory()->getContents() as $itemArmor) {
            $item = ExtraVanillaItems::getItem($itemArmor);

            if ($item instanceof self && $item->getDefensePoints() > 0) {
                $total += $item->getDefensePoints();
            } else {
                $total += $itemArmor->getDefensePoints();
            }
        }
        return $total;
    }

    public function getDefensePoints(): int
    {
        return $this->defensePoints;
    }

    public function getMaxDurability(): int
    {
        return $this->maxDurability;
    }
}