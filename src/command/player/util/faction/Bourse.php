<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\faction;

use CortexPE\Commando\BaseCommand;
use NCore\handler\Cache;
use pocketmine\command\CommandSender;
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
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $items = Cache::$dynamic["bourse"];
        $bar = "§l§8-----------------------";

        $sender->sendMessage($bar);

        foreach ($items as $item) {
            list($name, , , , $sell) = explode(":", $item);

            $sender->sendMessage("§e" . $name . "§f - Prix de vente: §e" . $sell . " §fpièces§e/u");
        }

        $sender->sendMessage($bar);
    }

    protected function prepare(): void
    {
    }
}