<?php /** @noinspection PhpUnused */

namespace Kitmap\command\util;

use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseCommand;
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
                $sender->sendMessage(Util::PREFIX . "Vous possèdez §9" . $sender->getNetworkSession()->getPing() . " §fde ping");
            }
        } else {
            /** @noinspection PhpDeprecationInspection */
            $target = Main::getInstance()->getServer()->getPlayerByPrefix($args["joueur"]);

            if (!$target instanceof Player) {
                if ($sender instanceof Player) {
                    $sender->sendMessage(Util::PREFIX . "Vous possèdez §9" . $sender->getNetworkSession()->getPing() . " §fde ping");
                }
                return;
            }
            $sender->sendMessage(Util::PREFIX . "Le joueur §9" . $target->getName() . "§f possède §9" . $target->getNetworkSession()->getPing() . "§f de ping");
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(true, "joueur"));
    }
}