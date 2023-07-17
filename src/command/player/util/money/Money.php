<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\money;

use CortexPE\Commando\args\TargetArgument;
use CortexPE\Commando\BaseCommand;
use NCore\Base;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Money extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "money",
            "Acceder à son montant de monnaie"
        );
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!isset($args["joueur"])) {
            if ($sender instanceof Player) {
                $sender->sendMessage(Util::PREFIX . "Vous possèdez §e" . Session::get($sender)->data["player"]["money"] . " §fpièces");
            }
        } else {
            $target = Base::getInstance()->getServer()->getPlayerByPrefix($args["joueur"]);

            if (!$target instanceof Player) {
                if ($sender instanceof Player) {
                    $sender->sendMessage(Util::PREFIX . "Vous possèdez §e" . Session::get($sender)->data["player"]["money"] . " §fpièces");
                }
                return;
            }
            $sender->sendMessage(Util::PREFIX . "Le joueur §e" . $target->getName() . "§f possède §e" . Session::get($target)->data["player"]["money"] . " §fpièces");
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetArgument("joueur", true));
    }
}