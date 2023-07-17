<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\rank;

use CortexPE\Commando\args\TargetArgument;
use CortexPE\Commando\BaseCommand;
use NCore\Base;
use NCore\handler\RankAPI;
use NCore\Session;
use NCore\Util;
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
            $target = Base::getInstance()->getServer()->getPlayerByPrefix($args["joueur"]);

            if (!RankAPI::hasRank($sender, "roi")) {
                $sender->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de faire cela");
                return;
            } else if (!$target instanceof Player) {
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