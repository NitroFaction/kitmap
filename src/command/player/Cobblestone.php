<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player;

use CortexPE\Commando\BaseCommand;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
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

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->data["cobblestone"]) {
                $session->data["cobblestone"] = false;
                $sender->sendMessage(Util::PREFIX . "Vous n'aurez plus les drops de cobblestone");
            } else {
                $session->data["cobblestone"] = true;
                $sender->sendMessage(Util::PREFIX . "Vous avez desormais les drops de cobblestone");
            }
        }
    }

    protected function prepare(): void
    {
    }
}