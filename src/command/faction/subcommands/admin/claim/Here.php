<?php

namespace Kitmap\command\faction\subcommands\admin\claim;

use CortexPE\Commando\BaseSubCommand;
use Kitmap\handler\Faction;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Here extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "here",
            "Regarde le nom du claim ou à qui il appartient"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $claim = Faction::inClaim($sender->getPosition()->getFloorX(), $sender->getPosition()->getFloorZ());

            if (!$claim[0]) {
                $sender->sendMessage(Util::PREFIX . "L'endroit ou vous êtes n'est claim par aucune faction");
                return;
            }

            $sender->sendMessage(Util::PREFIX . "L'endroit ou vous êtes est claim par la faction §q" . $claim[1]);
        }
    }

    protected function prepare(): void
    {
    }
}