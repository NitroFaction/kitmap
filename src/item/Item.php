<?php

namespace Kitmap\item;

use Kitmap\entity\SplashPotion as SplashPotionEntity;
use Kitmap\handler\trait\CooldownTrait;
use pocketmine\entity\Location;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\inventory\ItemDamageEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\Durable as PmDurable;
use pocketmine\item\Item as PmItem;
use pocketmine\item\PotionType;
use pocketmine\item\Releasable;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\world\sound\ItemBreakSound;
use pocketmine\world\sound\ThrowSound;

class Item
{
    use CooldownTrait;

    // False = no return in the event
    // True = return in the event
    // Cancel in the function not automatic

    public function onUse(PlayerItemUseEvent $event): bool
    {
        return false;
    }

    public function onInteract(PlayerInteractEvent $event): bool
    {
        return false;
    }

    public function createPotion(Vector3 $directionVector, Player $player, PotionType $type): bool
    {
        $location = $player->getLocation();

        $projectile = new SplashPotionEntity(Location::fromObject($player->getEyePos(), $player->getWorld(), $location->yaw, $location->pitch), $player, $type);
        $projectile->setMotion($directionVector->multiply(0.5));

        $projectileEv = new ProjectileLaunchEvent($projectile);
        $projectileEv->call();

        if ($projectileEv->isCancelled()) {
            $projectile->flagForDespawn();
            return false;
        }

        $projectile->spawnToAll();
        $location->getWorld()->addSound($location, new ThrowSound());

        return true;
    }

    public function onDamage(ItemDamageEvent $event): bool
    {
        return false;
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function onAttack(EntityDamageEvent $event, Player $player): bool
    {
        return false;
    }

    public function onBreak(BlockBreakEvent $event): bool
    {
        return false;
    }

    public function onConsume(PlayerItemConsumeEvent $event): bool
    {
        return false;
    }

    public function applyDamage(Player $player, float $damage = 1): void
    {
        $item = $player->getInventory()->getItemInHand();

        if ($item instanceof PmDurable) {
            $item->applyDamage($damage);

            if ($item->isBroken()) {
                $this->destroy($player);
            } else {
                $player->getInventory()->setItemInHand($item);
            }
        }
    }

    private function destroy(Player $player): void
    {
        $player->getInventory()->setItemInHand(VanillaItems::AIR());
        $player->broadcastSound(new ItemBreakSound());
    }

    public function projectileSucces(Player $player, PmItem $item, bool $pop = true): void
    {
        $player->resetItemCooldown($item);
        $player->setUsingItem($item instanceof Releasable && $item->canStartUsingItem($player));

        if ($player->getGamemode() !== GameMode::CREATIVE() && $pop) {
            $newItem = $item->pop()->isNull() ? VanillaItems::AIR() : $item;
            $player->getInventory()->setItemInHand($newItem);
        }
    }
}