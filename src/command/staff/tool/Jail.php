<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\tool;

use CortexPE\Commando\args\TargetArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\world\Position;

class Jail extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "jail",
            "Téléporte un joueur et le freeze dans la prison du serveur"
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
        } elseif (Session::get($target)->inCooldown("combat")) {
            $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas mettre en prison un joueur en combat");
            return;
        }

        $target->teleport(new Position(0.5, 62, -49.5, Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()));
        $target->setImmobile();

        $sender->sendMessage(Util::PREFIX . "Vous venez de mettre en prison §e" . $target->getName());

        $target->sendMessage(Util::PREFIX . "Vous venez d'être mis en prison par le staff §e" . $sender->getName());
        $target->sendTitle("§ePrison !", "§fVous avez été mis en prison par §e" . $sender->getName());
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetArgument("joueur"));
    }
}