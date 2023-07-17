<?php

namespace NCore\command\sub\faction;

use CortexPE\Commando\BaseSubCommand;
use NCore\Base;
use NCore\handler\Cache;
use NCore\handler\FactionAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class Leave extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "leave", "Quitte sa faction actuel", ["quit"]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if (!FactionAPI::hasFaction($sender)) {
                $sender->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction");
                return;
            } else if (FactionAPI::getFactionRank($sender) === "leader") {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas quitter votre faction si vous en êtes chef");
                return;
            }

            $faction = $session->data["player"]["faction"];
            $rank = FactionAPI::getFactionRank($faction, $sender->getName());

            unset(Cache::$factions[$faction]["members"][$rank . "s"][array_search($sender->getName(), Cache::$factions[$faction]["members"][$rank . "s"])]);

            $session->data["player"]["faction"] = null;
            $session->data["player"]["faction_chat"] = false;

            Cache::$factions[$faction]["logs"][time()] = "§e" . $sender->getName() . " §fquitte la faction";

            $sender->sendMessage(Util::PREFIX . "Vous venez de quitter votre faction");
            FactionAPI::broadcastMessage($faction, "§e[§fF§e] §fLe joueur §e" . $sender->getName() . " §fvient de quitter votre faction");
        }
    }

    protected function prepare(): void
    {
    }
}