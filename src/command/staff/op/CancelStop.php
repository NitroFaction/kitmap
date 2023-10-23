<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\BaseCommand;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;

class CancelStop extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "cancelstop",
            "Arrête l'arrêt du serveur"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (is_null(Stop::$task)) {
            $sender->sendMessage(Util::PREFIX . "Le serveur n'est pas entrain de redémarrer");
            return;
        }

        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le redémarrage du serveur vient d'être annulé");
        Main::getInstance()->getServer()->broadcastPopup(Util::PREFIX . "Le redémarrage du serveur vient d'être annulé");

        Stop::$task->getHandler()->cancel();
        Stop::$task = null;
    }

    protected function prepare(): void
    {
    }
}