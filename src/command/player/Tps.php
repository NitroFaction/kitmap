<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player;

use CortexPE\Commando\BaseCommand;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;

class Tps extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "tps",
            "Affiche les tps du serveur en temps réel"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $server = Main::getInstance()->getServer();
        $bar = "§l§8-----------------------";

        $sender->sendMessage($bar);
        $sender->sendMessage(Util::PREFIX . "Tps Actuel: §9" . $server->getTicksPerSecond() . " §f(§9" . $server->getTickUsage() . "%§f)");
        $sender->sendMessage(Util::PREFIX . "Tps en Moyenne: §9" . $server->getTicksPerSecondAverage() . " §f(§9" . $server->getTickUsageAverage() . "%§f)");
        $sender->sendMessage($bar);
    }

    protected function prepare(): void
    {
    }
}