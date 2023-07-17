<?php

namespace NCore\command\sub\faction\admin;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use NCore\Base;
use NCore\handler\Cache;
use NCore\handler\FactionAPI;
use NCore\Util;
use pocketmine\command\CommandSender;

class Leader extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "leader", "Modifie le chef d'une faction");
        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $faction = strtolower($args["faction"]);
        $member = $args["membre"];

        if (!FactionAPI::exist($faction)) {
            $sender->sendMessage(Util::PREFIX . "La faction §e" . $faction . " §fn'existe pas");
            return;
        } else if (!in_array($member, FactionAPI::getFactionMembers($faction, false))) {
            $sender->sendMessage(Util::PREFIX . "Ce joueur n'est pas dans la faction que vous avez indiqué (verifiez les majs)");
            return;
        } else if ($member === $sender->getName()) {
            $sender->sendMessage(Util::PREFIX . "Ce joueur est déjà le chef de sa faction");
            return;
        }

        $rank = FactionAPI::getFactionRank($faction, $member);

        unset(Cache::$factions[$faction]["members"][$rank . "s"][array_search($member, Cache::$factions[$faction]["members"][$rank . "s"])]);
        Cache::$factions[$faction]["members"]["leader"] = $member;

        FactionAPI::broadcastMessage($faction, "§e[§fF§e] §fLe joueur §e" . $member . " §fest votre nouveau chef de faction");
        $sender->sendMessage(Util::PREFIX . "Vous venez de mettre la tête de la faction §e" . $faction . " §fà §e" . $member);
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("faction"));
        $this->registerArgument(1, new RawStringArgument("membre"));
    }
}