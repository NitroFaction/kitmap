<?php

namespace Kitmap\command\faction\subcommands\admin;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\Faction;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;

class Leader extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Main::getInstance(), "leader", "Modifie le chef d'une faction");
        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $faction = strtolower($args["faction"]);
        $member = $args["membre"];

        return;

        if (!Faction::exists($faction)) {
            $sender->sendMessage(Util::PREFIX . "La faction §9" . $faction . " §fn'existe pas");
            return;
        } else if (!in_array($member, Faction::getFactionMembers($faction, false))) {
            $sender->sendMessage(Util::PREFIX . "Ce joueur n'est pas dans la faction que vous avez indiqué (verifiez les majs)");
            return;
        } else if ($member === $sender->getName()) {
            $sender->sendMessage(Util::PREFIX . "Ce joueur est déjà le chef de sa faction");
            return;
        }

        $rank = Faction::getFactionRank($faction, $member);

        unset(Cache::$factions[$faction]["members"][$rank . "s"][array_search($member, Cache::$factions[$faction]["members"][$rank . "s"])]);
        Cache::$factions[$faction]["members"]["leader"] = $member;

        Faction::broadcastMessage($faction, "§9[§fF§9] §fLe joueur §9" . $member . " §fest votre nouveau chef de faction");
        $sender->sendMessage(Util::PREFIX . "Vous venez de mettre la tête de la faction §9" . $faction . " §fà §9" . $member);
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("faction"));
        $this->registerArgument(1, new RawStringArgument("membre"));
    }
}