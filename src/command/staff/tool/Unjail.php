<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\tool;

use CortexPE\Commando\args\TargetArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Unjail extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "unjail",
            "Sort un joueur de la prison"
        );

        $this->setPermission("staff.group");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $target = $args["joueur"];
        $target = Main::getInstance()->getServer()->getPlayerByPrefix($target);

        if (!$target instanceof Player) {
            $sender->sendMessage(Util::PREFIX . "Le joueur indiqué n'est pas connecté sur le serveur");
            return;
        }

        $target->teleport(Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
        $target->setImmobile(false);

        $sender->sendMessage(Util::PREFIX . "Vous venez de sortir de prison le joueur §e" . $target->getName());
        $target->sendMessage(Util::PREFIX . "Le staff §e" . $sender->getName() . " §fvient de vous sortir de prison");
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetArgument("joueur"));
    }
}