<?php /** @noinspection PhpUnused */

namespace NCore\command\player;

use CortexPE\Commando\BaseCommand;
use NCore\Base;
use NCore\handler\OtherAPI;
use NCore\Session;
use NCore\task\TeleportationTask;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Mine extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "mine",
            "Se téléporte à la mine"
        );
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->inCooldown("combat")) {
                $sender->sendMessage(Util::PREFIX . "Cette commande est interdite en combat");
                return;
            } else if ($session->inCooldown("teleportation")) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas executer cette commande en teleportation");
                return;
            }

            $time = OtherAPI::getTpTime($sender);

            $sender->sendMessage(Util::PREFIX . "Vous allez être teleporté dans §e" . max($time, 0) . " §fseconde(s), veuillez ne pas bouger");
            $sender->getEffects()->add(new EffectInstance(VanillaEffects::BLINDNESS(), 20 * ($time + 1), 1, false));

            Base::getInstance()->getScheduler()->scheduleRepeatingTask(new TeleportationTask($sender, Base::getInstance()->getServer()->getWorldManager()->getWorldByName("farm")->getSpawnLocation()), 20);
            $session->setCooldown("teleportation", $time, [OtherAPI::getPlace($sender)]);
        }
    }

    protected function prepare(): void
    {
    }
}