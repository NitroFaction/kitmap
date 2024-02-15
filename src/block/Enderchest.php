<?php

namespace Kitmap\block;

use Kitmap\handler\Cache;
use Kitmap\handler\Pack;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\event\player\PlayerInteractEvent;

class Enderchest extends Block
{
    public function onInteract(PlayerInteractEvent $event): bool
    {
        $player = $event->getPlayer();
        $position = $event->getBlock()->getPosition();

        if (
            $event->getAction() === $event::RIGHT_CLICK_BLOCK &&
            $position->getWorld() === Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()
        ) {
            $format = $position->x . ":" . $position->y . ":" . $position->z;
            $pack = Cache::$config["enderchest"][$format] ?? null;

            if (!is_null($pack)) {
                $event->cancel();

                Util::removeCurrentWindow($player);
                Pack::openPackCategoryUI($player, $pack);
            }
        }

        return false;
    }
}