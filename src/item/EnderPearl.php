<?php

namespace Kitmap\item;

use Kitmap\entity\EnderPearl as EnderPearlEntity;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\entity\Location;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\world\sound\ThrowSound;

class EnderPearl extends Item
{
    public function onUse(PlayerItemUseEvent $event): bool
    {
        $player = $event->getPlayer();
        $session = Session::get($player);

        $directionVector = $event->getDirectionVector();
        $location = $player->getLocation();

        $item = $player->getInventory()->getItemInHand();

        $event->cancel();

        if ($session->inCooldown("enderpearl")) {
            $player->sendMessage(Util::PREFIX . "Veuillez attendre §9" . ($session->getCooldownData("enderpearl")[0] - time()) . " §fsecondes avant de relancer une nouvelle perle");
            $event->cancel();
            return false;
        } else if ($session->inCooldown("_antipearl")) {
            $player->sendTip(Util::PREFIX . "Veuillez attendre §9" . ($session->getCooldownData("_antipearl")[0] - time()) . " §fsecondes avant de relancer une nouvelle perle");
            $event->cancel();
            return false;
        } else if (!is_null($item->getNamedTag()->getTag("partneritem"))) {
            $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas utiliser cette perle");
            $event->cancel();
            return false;
        }

        $projectile = new EnderPearlEntity(Location::fromObject($player->getEyePos(), $player->getWorld(), $location->yaw, $location->pitch), $player);
        $projectile->setMotion($directionVector->multiply(1.5));

        $projectileEv = new ProjectileLaunchEvent($projectile);
        $projectileEv->call();

        if ($projectileEv->isCancelled()) {
            $projectile->flagForDespawn();
            return false;
        }

        $session->setCooldown("enderpearl", 15, [$player->getPosition()]);
        $projectile->spawnToAll();

        $location->getWorld()->addSound($location, new ThrowSound());
        $this->projectileSucces($player, $item);

        return false;
    }
}