<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player\rank;

use CortexPE\Commando\args\TargetArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Rank;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class StealSkin extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "stealskin",
            "Vole le skin d'un autre joueur"
        );
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $target = Main::getInstance()->getServer()->getPlayerByPrefix($args["joueur"]);

            if (!Rank::hasRank($sender, "roi")) {
                $sender->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de faire cela");
                return;
            } elseif (!$target instanceof Player) {
                $sender->sendMessage(Util::PREFIX . "Le joueur n'éxiste pas ou n'est pas connecté sur le serveur");
                return;
            }

            $sender->setSkin(Session::get($target)->data["skin"]);
            $sender->sendSkin();

            $sender->sendMessage(Util::PREFIX . "Vous venez de voler le skin de §e" . $target->getName());
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetArgument("joueur"));
    }
}