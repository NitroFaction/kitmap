<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player;

use CortexPE\Commando\BaseCommand;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
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

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
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
            $sender->sendMessage(Util::PREFIX . "Vous êtes en combat encore §e" . ($data[0] - time()) . " §fseconde(s)");
        }
    }

    protected function prepare(): void
    {
    }
}