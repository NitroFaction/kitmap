<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player;

use CortexPE\Commando\BaseCommand;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Xyz extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "xyz",
            "Active/Désactive les coordonnées en haut à gauche"
        );

        $this->setAliases(["cos"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);
            $pk = new GameRulesChangedPacket();

            if ($session->data["xyz"]) {
                $session->data["xyz"] = false;
                $sender->sendMessage(Util::PREFIX . "Vous venez de désactiver les coordonnées");
            } else {
                $session->data["xyz"] = true;
                $sender->sendMessage(Util::PREFIX . "Vous venez d'activer les coordonnées");
            }

            $pk->gameRules = ["showcoordinates" => new BoolGameRule($session->data["xyz"], false)];
            $sender->getNetworkSession()->sendDataPacket($pk);
        }
    }

    protected function prepare(): void
    {
    }
}