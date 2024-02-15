<?php

namespace Kitmap\item;

use Kitmap\Session;
use Kitmap\Util;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\player\PlayerItemConsumeEvent;

class StrengthCookie extends Item
{
    public function onConsume(PlayerItemConsumeEvent $event): bool
    {
        $player = $event->getPlayer();
        $session = Session::get($player);

        if ($session->inCooldown("cookie_strength")) {
            $player->sendMessage(Util::PREFIX . "Veuillez attendre §9" . ($session->getCooldownData("cookie_strength")[0] - time()) . " §fsecondes avant de remanger un cookie de force");
            $event->cancel();
        } else {
            $player->getEffects()->add(new EffectInstance(VanillaEffects::STRENGTH(), (240 * 20), 0, false));
            $session->setCooldown("cookie_strength", 25);
        }
        return false;
    }
}