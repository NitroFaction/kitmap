<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\faction;

use CortexPE\Commando\BaseCommand;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
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
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);
            $pk = new GameRulesChangedPacket();

            if ($session->data["player"]["xyz"]) {
                $session->data["player"]["xyz"] = false;
                $sender->sendMessage(Util::PREFIX . "Vous venez de désactiver les coordonnées");
            } else {
                $session->data["player"]["xyz"] = true;
                $sender->sendMessage(Util::PREFIX . "Vous venez d'activer les coordonnées");
            }

            $pk->gameRules = ["showcoordinates" => new BoolGameRule($session->data["player"]["xyz"], false)];
            $sender->getNetworkSession()->sendDataPacket($pk);
        }
    }

    protected function prepare(): void
    {
    }
}