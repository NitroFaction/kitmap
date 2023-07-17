<?php /** @noinspection PhpUnused */

namespace NCore\command\staff\server;

use CortexPE\Commando\BaseCommand;
use NCore\handler\Cache;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;

class Save extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "save",
            "Sauvegarde les données des factions, hdv, etc.."
        );

        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        Cache::saveCache();
        $sender->sendMessage(Util::PREFIX . "Vous venez de sauvegarder les données du serveur");
    }

    protected function prepare(): void
    {
    }
}