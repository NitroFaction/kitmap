<?php /** @noinspection PhpUnused */

namespace NCore\command\staff\server;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TargetArgument;
use CortexPE\Commando\BaseCommand;
use NCore\Base;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Clearcooldown extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "clearcooldown",
            "Supprime un cooldown ou celui d'un autre joueur"
        );

        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $target = $args["joueur"] ?? $sender->getName();
        $cooldown = $args["cooldown"];

        if ($target === "@a") {
            Util::allSelectorExecute($sender, $this->getName(), $args);
            return;
        }

        $target = Base::getInstance()->getServer()->getPlayerByPrefix($target);

        if (!$target instanceof Player) {
            $sender->sendMessage(Util::PREFIX . "Le joueur indiqué n'est pas connecté sur le serveur");
            return;
        }
        $targetSession = Session::get($target);

        if ($target->getName() === $sender->getName()) {
            $sender->sendMessage(Util::PREFIX . "Vous venez de clear votre cooldown §e" . $cooldown);
        } else {
            $sender->sendMessage(Util::PREFIX . "Vous venez de clear le cooldown §e" . $cooldown . " §fdu joueur §e" . $target->getName());
            $target->sendMessage(Util::PREFIX . "Un staff a clear votre cooldown §e" . $cooldown . " §f!");
        }

        if ($targetSession->inCooldown($cooldown)) {
            $targetSession->removeCooldown($cooldown);
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("cooldown"));
        $this->registerArgument(1, new TargetArgument("joueur", true));
    }
}