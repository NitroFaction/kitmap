<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\faction;

use CortexPE\Commando\BaseCommand;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Cobblestone extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "cobblestone",
            "Active ou désactive le drop des cobblestone"
        );
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->data["player"]["cobblestone"]) {
                $session->data["player"]["cobblestone"] = false;
                $sender->sendMessage(Util::PREFIX . "Vous n'aurez plus les drops de cobblestone");
            } else {
                $session->data["player"]["cobblestone"] = true;
                $sender->sendMessage(Util::PREFIX . "Vous avez desormais les drops de cobblestone");
            }
        }
    }

    protected function prepare(): void
    {
    }
}