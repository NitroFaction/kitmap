<?php /** @noinspection PhpUnused */

namespace NCore\command\staff\tool;

use CortexPE\Commando\BaseCommand;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class StaffChat extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "staffchat",
            "Active le staffchat"
        );

        $this->setPermission("staff.group");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->data["player"]["staff_chat"]) {
                $sender->sendMessage(Util::PREFIX . "Vous venez de désactiver le staffchat");
                $session->data["player"]["staff_chat"] = false;
            } else {
                $sender->sendMessage(Util::PREFIX . "Vous venez d'activer le staffchat");
                $session->data["player"]["staff_chat"] = true;
            }
        }
    }

    protected function prepare(): void
    {
    }
}