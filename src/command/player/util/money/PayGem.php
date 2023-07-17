<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\money;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\TargetArgument;
use CortexPE\Commando\BaseCommand;
use NCore\Base;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class PayGem extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "paygem",
            "Envoyer des gemmes à un autre joueur"
        );
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $target = Base::getInstance()->getServer()->getPlayerByPrefix($args["joueur"]);
            $amount = intval($args["montant"]);

            $senderSession = Session::get($sender);

            if (!$target instanceof Player) {
                $sender->sendMessage(Util::PREFIX . "Le joueur indiqué n'est pas connecté sur le serveur");
                return;
            } else if (1 > $amount) {
                $sender->sendMessage(Util::PREFIX . "Le montant que vous avez inscrit est invalide");
                return;
            } else if (floor($amount) > $senderSession->data["player"]["gem"]) {
                $sender->sendMessage(Util::PREFIX . "Vos gemmes sont infèrieur à §e" . floor($amount));
                return;
            }

            $targetSession = Session::get($target);
            $gem = floor($amount);

            $targetSession->addValue("gem", $gem);
            $senderSession->addValue("gem", $gem, true);

            $sender->sendMessage(Util::PREFIX . "Vous avez envoyé un montant de gemmes égal à §e" . $gem . " §fà §e" . $target->getName());
            $target->sendMessage(Util::PREFIX . "Vous avez recu un montant de gemmes égal à §e" . $gem . " §fde la part de §e" . $sender->getName());
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetArgument("joueur"));
        $this->registerArgument(1, new IntegerArgument("montant"));
    }
}