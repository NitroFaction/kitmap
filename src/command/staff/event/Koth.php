<?php /** @noinspection PhpUnused */

namespace NCore\command\staff\event;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use NCore\Base;
use NCore\task\repeat\event\KothTask;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use skymin\bossbar\BossBarAPI;

class Koth extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "koth",
            "Commence ou arrête un event koth !"
        );

        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        switch ($args["opt"]) {
            case "start":
                if (is_numeric(KothTask::$currentKoth)) {
                    $sender->sendMessage(Util::PREFIX . "Un event §eKOTH §fest déjà en cours... Vous pouvez l'arrêter avec la commande §e/koth end");
                    return;
                }

                KothTask::$currentKoth = 180;
                Base::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Un event §eKOTH §fvient de commencer ! Vous pouvez vous y téléporter grace à la commande §e/event koth");
                break;
            case "end":
                KothTask::$currentKoth = null;
                KothTask::$currentPlayer = null;

                foreach (Base::getInstance()->getServer()->getOnlinePlayers() as $player) {
                    BossBarAPI::getInstance()->hideBossBar($player, 1);
                }

                Base::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "L'event §eKOTH §fa été arrêté, pas de stuff :/");
                break;
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("opt", ["start", "end"]));
    }
}