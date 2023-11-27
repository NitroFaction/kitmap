<?php /** @noinspection PhpUnused */

namespace Kitmap\command\util;

use CortexPE\Commando\BaseCommand;
use Element\util\args\TargetArgument;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Ping extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "ping",
            "Récupére la latence entre un joueur et le serveur"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!isset($args["joueur"])) {
            if ($sender instanceof Player) {
                $sender->sendMessage(Util::PREFIX . "Vous possèdez §q" . $sender->getNetworkSession()->getPing() . " §fde ping");
            }
        } else {
            /** @noinspection PhpDeprecationInspection */
            $target = Main::getInstance()->getServer()->getPlayerByPrefix($args["joueur"]);

            if (!$target instanceof Player) {
                if ($sender instanceof Player) {
                    $sender->sendMessage(Util::PREFIX . "Vous possèdez §q" . $sender->getNetworkSession()->getPing() . " §fde ping");
                }
                return;
            }
            $sender->sendMessage(Util::PREFIX . "Le joueur §q" . $target->getName() . "§f possède §q" . $target->getNetworkSession()->getPing() . "§f de ping");
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetArgument("joueur", true));
    }
}