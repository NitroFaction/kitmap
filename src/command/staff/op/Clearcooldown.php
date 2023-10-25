<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TargetArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
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

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $target = $args["joueur"] ?? $sender->getName();
        $cooldown = $args["cooldown"];

        if ($target === "@a") {
            Util::allSelectorExecute($sender, $this->getName(), $args);
            return;
        }

        /** @noinspection PhpDeprecationInspection */
        $target = Main::getInstance()->getServer()->getPlayerByPrefix($target);

        if (!$target instanceof Player) {
            $sender->sendMessage(Util::PREFIX . "Le joueur indiqué n'est pas connecté sur le serveur");
            return;
        }

        $targetSession = Session::get($target);

        if ($target->getName() === $sender->getName()) {
            $sender->sendMessage(Util::PREFIX . "Vous venez de clear votre cooldown §6" . $cooldown);
        } else {
            $sender->sendMessage(Util::PREFIX . "Vous venez de clear le cooldown §6" . $cooldown . " §fdu joueur §6" . $target->getName());
            $target->sendMessage(Util::PREFIX . "Un staff a clear votre cooldown §6" . $cooldown . " §f!");
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