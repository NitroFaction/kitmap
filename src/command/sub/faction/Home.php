<?php

namespace NCore\command\sub\faction;

use CortexPE\Commando\BaseSubCommand;
use NCore\Base;
use NCore\handler\Cache;
use NCore\handler\FactionAPI;
use NCore\handler\OtherAPI;
use NCore\Session;
use NCore\task\TeleportationTask;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\player\Player;
use pocketmine\world\Position;

class Home extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "home", "Se téléporter à l'home d'une faction");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            $permission = FactionAPI::hasPermission($sender, $this->getName());

            if (is_null($permission)) {
                $sender->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction");
                return;
            } else if (!$permission) {
                $sender->sendMessage(Util::PREFIX . "Vous ne possèdez pas les permissions necessaire dans votre faction pour faire cela");
                return;
            } else if ($session->inCooldown("combat")) {
                $sender->sendMessage(Util::PREFIX . "Cette commande est interdite en combat");
                return;
            } else if ($session->inCooldown("teleportation")) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas executer cette commande en teleportation");
                return;
            }

            $faction = $session->data["player"]["faction"];
            $home = Cache::$factions[$faction]["home"];

            if (is_null($home)) {
                $sender->sendMessage(Util::PREFIX . "Votre faction n'a pas encore définit de home");
                return;
            }
            list($x, $y, $z) = explode(":", Cache::$factions[$faction]["home"]);

            if (FactionAPI::inPlot(intval($x), intval($z))[1] !== $faction) {
                $sender->sendMessage(Util::PREFIX . "Votre home n'est pas dans votre claim");
                Cache::$factions[$faction]["home"] = "0:0:0";
                return;
            }

            $position = new Position(intval($x), intval($y), intval($z), Base::getInstance()->getServer()->getWorldManager()->getDefaultWorld());
            $time = OtherAPI::getTpTime($sender);

            $sender->sendMessage(Util::PREFIX . "Vous allez être teleporté dans §e" . max($time, 0) . " §fseconde(s), veuillez ne pas bouger");
            $sender->getEffects()->add(new EffectInstance(VanillaEffects::BLINDNESS(), 20 * ($time + 1), 1, false));

            Base::getInstance()->getScheduler()->scheduleRepeatingTask(new TeleportationTask($sender, $position), 20);
            $session->setCooldown("teleportation", $time, [OtherAPI::getPlace($sender)]);
        }
    }

    protected function prepare(): void
    {
    }
}