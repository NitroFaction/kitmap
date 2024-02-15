<?php

namespace Kitmap\item;

use Kitmap\Util;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\PotionType;
use pocketmine\item\VanillaItems;

class PotionLauncher extends Item
{
    public function onUse(PlayerItemUseEvent $event): bool
    {
        $player = $event->getPlayer();

        $directionVector = $event->getDirectionVector();
        $item = $event->getItem();

        $event->cancel();

        if ($player->getNetworkSession()->getPlayerInfo()->getExtraData()["CurrentInputMode"] !== 2) {
            $player->sendMessage(Util::PREFIX . "Le potion launcher n'est disponible que pour les tactiles");
            return true;
        } else if (1 > Util::getItemCount($player, VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_HEALING()))) {
            $player->sendMessage(Util::PREFIX . "Vous n'avez pas de potion dans votre inventaire");
            return true;
        }

        $succes = $this->createPotion($directionVector, $player, PotionType::STRONG_HEALING());

        if ($succes) {
            $this->projectileSucces($player, $item, false);
            $player->getInventory()->removeItem(VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_HEALING()));
        }

        return true;
    }
}