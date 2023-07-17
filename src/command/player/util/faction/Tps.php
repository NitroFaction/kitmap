<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\faction;

use CortexPE\Commando\BaseCommand;
use NCore\Base;
use NCore\Util;
use pocketmine\command\CommandSender;
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
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $server = Base::getInstance()->getServer();
        $bar = "§l§8-----------------------";

        $sender->sendMessage($bar);
        $sender->sendMessage(Util::PREFIX . "Tps Actuel: §e" . $server->getTicksPerSecond() . " §f(§e" . $server->getTickUsage() . "%§f)");
        $sender->sendMessage(Util::PREFIX . "Tps en Moyenne: §e" . $server->getTicksPerSecondAverage() . " §f(§e" . $server->getTickUsageAverage() . "%§f)");
        $sender->sendMessage($bar);
    }

    protected function prepare(): void
    {
    }
}