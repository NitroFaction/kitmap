<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff;

use CortexPE\Commando\BaseCommand;
use Element\util\args\OptionArgument;
use Kitmap\Main;
use Kitmap\task\repeat\DominationTask;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;

class Domination extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "domination",
            "Commence ou arrête un event domination !"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        switch ($args["opt"]) {
            case "start":
                if (30 > count(Main::getInstance()->getServer()->getOnlinePlayers())) {
                    $sender->sendMessage(Util::PREFIX . "L'event domination demande au minimum §q30 §fjoueurs avant d'être lancé");
                    return;
                } else if (DominationTask::$currentDomination) {
                    $sender->sendMessage(Util::PREFIX . "Un event §qdomination §fest déjà en cours... Vous pouvez l'arrêter avec la commande §q/domination end");
                    return;
                }

                DominationTask::$currentDomination = true;
                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Un event §qdomination §fvient de commencer ! Vous pouvez vous y téléporter grace à la commande §q/event domination");
                break;
            case "end":
                DominationTask::$currentDomination = false;
                DominationTask::$factions = [];
                DominationTask::$zones = [];
                DominationTask::$time = 900;

                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "L'event §qdomination §fa été arrêté");
                break;
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("opt", ["start", "end"]));
    }
}