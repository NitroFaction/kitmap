<?php

namespace Kitmap\command\faction\subcommands\admin;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use Kitmap\command\faction\subcommands\Logs as FactionLogs;
use Kitmap\handler\Faction;
use Kitmap\handler\Rank;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class Logs extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Main::getInstance(), "logs", "Regarder les logs d'une faction");
        $this->setPermissions([Rank::GROUP_STAFF]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $faction = strtolower($args["faction"]);

            if (!Faction::exists($faction)) {
                $sender->sendMessage(Util::PREFIX . "La faction §9" . $faction . " §fn'existe pas");
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