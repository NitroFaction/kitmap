<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\BaseCommand;
use Element\util\args\TargetArgument;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class PvClear extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "pvclear",
            "Permet de clear des joueurs"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            /** @noinspection PhpDeprecationInspection */
            $player = Main::getInstance()->getServer()->getPlayerByPrefix($args["joueur"]);

            if (!$player instanceof Player) {
                $sender->sendMessage(Util::PREFIX . "Le joueur indiqué n'est pas connecté sur le serveur");
                return;
            }

            $session = Session::get($player);
            unset($session->data["private_vaults"]);

            $sender->sendMessage(Util::PREFIX . "Vous venez de clear les pv de §q" . $sender->getName());
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetArgument("joueur"));
    }
}