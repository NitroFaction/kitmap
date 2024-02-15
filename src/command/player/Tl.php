<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player;

use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Faction;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
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

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            if (!Faction::hasFaction($sender)) {
                $sender->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction");
                return;
            }

            $faction = Session::get($sender)->data["faction"];

            $x = $sender->getPosition()->getFloorX();
            $y = $sender->getPosition()->getFloorY();
            $z = $sender->getPosition()->getFloorZ();

            Faction::broadcastMessage($faction, "§9[§fF§9] §f" . $sender->getName() . " " . Util::PREFIX . "X: §9" . $x . "§f, Y: §9" . $y . "§f, Z: §9" . $z);
        }
    }

    protected function prepare(): void
    {
    }
}