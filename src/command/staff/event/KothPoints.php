<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\event;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\Main;
use Kitmap\task\repeat\event\KothPointsTask;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;

class KothPoints extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "kothpoints",
            "Commence ou arrête un event koth à points !"
        );

        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        switch ($args["opt"]) {
            case "start":
                if (is_numeric(KothPointsTask::$currentKothPoints)) {
                    $sender->sendMessage(Util::PREFIX . "Un event §eKOTH POINTS §fest déjà en cours... Vous pouvez l'arrêter avec la commande §e/koth end");
                    return;
                }

                KothPointsTask::$points = [];
                KothPointsTask::$currentKothPoints = 180;

                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Un event §eKOTH POINTS §fvient de commencer ! Vous pouvez vous y téléporter grace à la commande §e/event kothpoints");
                break;
            case "end":
                KothPointsTask::$currentKothPoints = null;

                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "L'event §eKOTH POINTS §fa été arrêté, pas de stuff :/");
                break;
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("opt", ["start", "end"]));
    }
}