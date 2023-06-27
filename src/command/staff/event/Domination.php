<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\event;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\Main;
use Kitmap\task\repeat\event\DominationTask;
use Kitmap\Util;
use pocketmine\command\CommandSender;
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

        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        switch ($args["opt"]) {
            case "start":
                if (30 > count(Main::getInstance()->getServer()->getOnlinePlayers())) {
                    $sender->sendMessage(Util::PREFIX . "L'event domination demande au minimum §e30 §fjoueurs avant d'être lancé");
                    return;
                } elseif (DominationTask::$currentDomination) {
                    $sender->sendMessage(Util::PREFIX . "Un event §edomination §fest déjà en cours... Vous pouvez l'arrêter avec la commande §e/domination end");
                    return;
                }

                DominationTask::$currentDomination = true;
                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Un event §edomination §fvient de commencer ! Vous pouvez vous y téléporter grace à la commande §e/event domination");
                break;
            case "end":
                DominationTask::$currentDomination = false;
                DominationTask::$factions = [];
                DominationTask::$zones = [];
                DominationTask::$time = 900;

                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "L'event §edomination §fa été arrêté");
                break;
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("opt", ["start", "end"]));
    }
}