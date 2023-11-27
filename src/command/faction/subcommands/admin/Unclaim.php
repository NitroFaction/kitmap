<?php

namespace Kitmap\command\faction\subcommands\admin;

use CortexPE\Commando\BaseSubCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\Faction;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Unclaim extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "unclaim",
            "Unclaim le claim ou vous êtes"
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

            $faction = $claim[1];
            $claim = Cache::$factions[$faction]["claim"];

            Cache::$factions[$faction]["claim"] = null;
            unset(Cache::$claims[$claim]);

            Cache::$factions[$faction]["logs"][time()] = "§q" . $sender->getName() . " §funclaim l'ancien claim §c(ADMIN)";
            Faction::broadcastMessage($faction, "§q[§fF§r§q] §fVotre faction vient de perdre son claim (Supprimé par un admin)");

            $sender->sendMessage(Util::PREFIX . "Vous venez d'unclaim le claim de la faction §q" . $faction);
        }
    }

    protected function prepare(): void
    {
    }
}