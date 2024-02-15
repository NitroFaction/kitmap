<?php /** @noinspection PhpUnused */

namespace Kitmap\command\util\money;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Pay extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "pay",
            "Envoyer de la monnaie à un autre joueur"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            if ($args["joueur"] === "@a") {
                Util::allSelectorExecute($sender, $this->getName(), $args);
                return;
            }

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
            } else if (floor($amount) > $senderSession->data["money"]) {
                $sender->sendMessage(Util::PREFIX . "Votre monnaie est infèrieur à §9" . floor($amount));
                return;
            }
            $targetSession = Session::get($target);
            $money = floor($amount);

            $targetSession->addValue("money", $money);
            $senderSession->addValue("money", $money, true);

            $sender->sendMessage(Util::PREFIX . "Vous avez envoyé un montant égal à §9" . $money . " §fà §9" . $target->getName());
            $target->sendMessage(Util::PREFIX . "Vous avez recu un montant d'argent égal à §9" . $money . " §fde la part de §9" . $sender->getName());
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(false, "joueur"));
        $this->registerArgument(1, new IntegerArgument("montant"));
    }
}