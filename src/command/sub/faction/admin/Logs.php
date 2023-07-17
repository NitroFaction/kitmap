<?php

namespace NCore\command\sub\faction\admin;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use NCore\Base;
use NCore\command\sub\faction\Logs as FactionLogs;
use NCore\handler\FactionAPI;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class Logs extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "logs", "Regarder les logs d'une faction");
        $this->setPermission("staff.group");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $faction = strtolower($args["faction"]);

            if (!FactionAPI::exist($faction)) {
                $sender->sendMessage(Util::PREFIX . "La faction §e" . $faction . " §fn'existe pas");
                return;
            }

            FactionLogs::showLogsForm($sender, $faction);
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("faction"));
    }
}