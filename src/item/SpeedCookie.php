<?php

namespace Kitmap\item;

use Kitmap\Session;
use Kitmap\Util;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\player\PlayerItemConsumeEvent;

class SpeedCookie extends Item
{
    public function onConsume(PlayerItemConsumeEvent $event): bool
    {
        $player = $event->getPlayer();
        $session = Session::get($player);

        if ($session->inCooldown("cookie_speed")) {
            $player->sendMessage(Util::PREFIX . "Veuillez attendre §9" . ($session->getCooldownData("cookie_speed")[0] - time()) . " §fsecondes avant de remanger un cookie de vitesse");
            $event->cancel();
        } else {
            $player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), (240 * 20), 0, false));
            $session->setCooldown("cookie_speed", 25);
        }
        return false;
    }
}