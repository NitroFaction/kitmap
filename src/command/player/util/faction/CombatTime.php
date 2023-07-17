<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\faction;

use CortexPE\Commando\BaseCommand;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class CombatTime extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "combattime",
            "Vous donne le temps restant ou vous êtes en combat"
        );
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if (!$session->inCooldown("combat")) {
                $sender->sendMessage(Util::PREFIX . "Vous n'êtes actuellement pas en combat");
                return;
            }

            $data = $session->getCooldownData("combat");
            $sender->sendMessage(Util::PREFIX . "Vous êtes encore en combat §e" . ($data[0] - time()) . " §fseconde(s)");
        }
    }

    protected function prepare(): void
    {
    }
}