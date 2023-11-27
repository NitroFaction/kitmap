<?php /** @noinspection PhpUnused */

namespace Kitmap\command\util\money;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\BaseCommand;
use Element\util\args\TargetArgument;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
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

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            /** @noinspection PhpDeprecationInspection */
            $target = Main::getInstance()->getServer()->getPlayerByPrefix($args["joueur"]);

            $amount = intval($args["montant"]);
            $senderSession = Session::get($sender);

            if (!$target instanceof Player) {
                $sender->sendMessage(Util::PREFIX . "Le joueur indiqué n'est pas connecté sur le serveur");
                return;
            } else if (1 > $amount) {
                $sender->sendMessage(Util::PREFIX . "Le montant que vous avez inscrit est invalide");
                return;
            } else if (floor($amount) > $senderSession->data["gem"]) {
                $sender->sendMessage(Util::PREFIX . "Vos gemmes sont infèrieur à §q" . floor($amount));
                return;
            }

            $targetSession = Session::get($target);
            $gem = floor($amount);

            $targetSession->addValue("gem", $gem);
            $senderSession->addValue("gem", $gem, true);

            $sender->sendMessage(Util::PREFIX . "Vous avez envoyé un montant de gemmes égal à §q" . $gem . " §fà §q" . $target->getName());
            $target->sendMessage(Util::PREFIX . "Vous avez recu un montant de gemmes égal à §q" . $gem . " §fde la part de §q" . $sender->getName());
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetArgument("joueur"));
        $this->registerArgument(1, new IntegerArgument("montant"));
    }
}