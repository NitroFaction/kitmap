<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\faction;

use CortexPE\Commando\BaseCommand;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Nightvision extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "nightvision",
            "Active la night vision"
        );

        $this->setAliases(["nv"]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->data["player"]["night_vision"]) {
                $sender->getEffects()->remove(VanillaEffects::NIGHT_VISION());
                $session->data["player"]["night_vision"] = false;

                $sender->sendMessage(Util::PREFIX . "Vous venez de désactiver la nightvision");
            } else {
                $sender->getEffects()->add(new EffectInstance(VanillaEffects::NIGHT_VISION(), 30 * 20, 255, false));
                $session->data["player"]["night_vision"] = true;

                $sender->sendMessage(Util::PREFIX . "Vous venez d'activer la nightvision");
            }
        }
    }

    protected function prepare(): void
    {
    }
}