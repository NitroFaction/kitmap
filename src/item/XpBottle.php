<?php

namespace Kitmap\item;

use Kitmap\Util;
use pocketmine\event\player\PlayerItemUseEvent;

class XpBottle extends Item
{
    public function onUse(PlayerItemUseEvent $event): bool
    {
        $player = $event->getPlayer();
        $item = $event->getItem();

        if (!is_null($item->getNamedTag()->getTag("xp_bottle"))) {
            $xp = $item->getNamedTag()->getInt("xp_bottle");

            $player->getXpManager()->addXpLevels($xp);
            $player->sendMessage(Util::PREFIX . "§fVous venez de récupérer §9" . $xp . " §fniveaux d'expérience !");

            $this->projectileSucces($player, $item);
            $event->cancel();

            return true;
        }
        return false;
    }
}