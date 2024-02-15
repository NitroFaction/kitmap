<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\args\TextArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Sudo extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "sudo",
            "Fait executer une commande ou parler un joueur"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($args["joueur"] === "@a") {
            Util::allSelectorExecute($sender, $this->getName(), $args);
            return;
        }

        /** @noinspection PhpDeprecationInspection */
        $player = Main::getInstance()->getServer()->getPlayerByPrefix(array_shift($args));

        if ($player instanceof Player) {
            $sudo = trim(implode(" ", $args));

            if ($sudo[0] === "/") {
                Main::getInstance()->getServer()->dispatchCommand($player, substr($sudo, 1));
                $sender->sendMessage(Util::PREFIX . "La commande indiqué a été faite par le joueur");
            } else {
                $player->chat($sudo);
                $sender->sendMessage(Util::PREFIX . "Le message indiqué a été envoyé par le joueur");
            }
        } else {
            $sender->sendMessage(Util::PREFIX . "Le joueur indiqué n'est pas connecté sur le serveur");
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(false, "joueur"));
        $this->registerArgument(1, new TextArgument("commande"));
    }
}