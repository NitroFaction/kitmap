<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util;

use CortexPE\Commando\args\TargetArgument;
use CortexPE\Commando\BaseCommand;
use NCore\Base;
use NCore\Util;
use pocketmine\command\CommandSender;
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
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!isset($args["joueur"])) {
            if ($sender instanceof Player) {
                $sender->sendMessage(Util::PREFIX . "Vous possèdez §e" . $sender->getNetworkSession()->getPing() . " §fde ping");
            }
        } else {
            $target = Base::getInstance()->getServer()->getPlayerByPrefix($args["joueur"]);

            if (!$target instanceof Player) {
                if ($sender instanceof Player) {
                    $sender->sendMessage(Util::PREFIX . "Vous possèdez §e" . $sender->getNetworkSession()->getPing() . " §fde ping");
                }
                return;
            }
            $sender->sendMessage(Util::PREFIX . "Le joueur §e" . $target->getName() . "§f possède §e" . $target->getNetworkSession()->getPing() . "§f de ping");
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetArgument("joueur", true));
    }
}