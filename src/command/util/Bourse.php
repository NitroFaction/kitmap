<?php /** @noinspection PhpUnused */

namespace Kitmap\command\util;

use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;

class Bourse extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "bourse",
            "Affiche le prix des agricultures actuel"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $items = Cache::$data["bourse"];
        $bar = "§l§8-----------------------";

        $sender->sendMessage($bar);

        foreach ($items as $item) {
            list($name, , , $sell) = explode(":", $item);

            $sender->sendMessage("§a" . $name . "§f - Prix de vente: §a" . $sell . " §fpièces§a/u");
        }

        $sender->sendMessage($bar);
    }

    protected function prepare(): void
    {
    }
}