<?php

namespace Kitmap\item;

use Kitmap\Session;
use Kitmap\Util;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\player\PlayerItemConsumeEvent;

class RegenerationCookie extends Item
{
    public function onConsume(PlayerItemConsumeEvent $event): bool
    {
        $player = $event->getPlayer();
        $session = Session::get($player);

        if ($session->inCooldown("cookie_regeneration")) {
            $player->sendMessage(Util::PREFIX . "Veuillez attendre §9" . ($session->getCooldownData("cookie_regeneration")[0] - time()) . " §fsecondes avant de remanger un cookie de regeneration");
            $event->cancel();
        } else {
            $player->getEffects()->add(new EffectInstance(VanillaEffects::REGENERATION(), (10 * 20), 0, false));
            $session->setCooldown("cookie_regeneration", 25);
        }
        return false;
    }
}