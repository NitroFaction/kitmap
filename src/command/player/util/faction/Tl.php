<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\faction;

use CortexPE\Commando\BaseCommand;
use NCore\handler\FactionAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Tl extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "tl",
            "Envoie ses coordonées à sa faction"
        );
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            if (!FactionAPI::hasFaction($sender)) {
                $sender->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction");
                return;
            }
            $faction = Session::get($sender)->data["player"]["faction"];

            $x = $sender->getPosition()->getFloorX();
            $y = $sender->getPosition()->getFloorY();
            $z = $sender->getPosition()->getFloorZ();

            FactionAPI::broadcastMessage($faction, "§e[§fF§e] §f" . $sender->getName() . " " . Util::PREFIX . "X: §e" . $x . "§f, Y: §e" . $y . "§f, Z: §e" . $z);
        }
    }

    protected function prepare(): void
    {
    }
}