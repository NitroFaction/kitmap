<?php

namespace Kitmap\item;

use Kitmap\Session;
use Kitmap\Util;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\player\PlayerItemConsumeEvent;

class CombinedCookie extends Item
{
    public function onConsume(PlayerItemConsumeEvent $event): bool
    {
        $player = $event->getPlayer();
        $session = Session::get($player);

        if ($session->inCooldown("cookie_combined")) {
            $player->sendMessage(Util::PREFIX . "Veuillez attendre §9" . ($session->getCooldownData("cookie_combined")[0] - time()) . " §fsecondes avant de remanger un cookie combiné");
            $event->cancel();
        } else {
            $player->getEffects()->add(new EffectInstance(VanillaEffects::ABSORPTION(), (10 * 20), 0, false));
            $player->getEffects()->add(new EffectInstance(VanillaEffects::REGENERATION(), (10 * 20), 0, false));
            $player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), (240 * 20), 0, false));
            $player->getEffects()->add(new EffectInstance(VanillaEffects::STRENGTH(), (240 * 20), 0, false));

            $session->setCooldown("cookie_combined", 25);
        }
        return false;
    }
}