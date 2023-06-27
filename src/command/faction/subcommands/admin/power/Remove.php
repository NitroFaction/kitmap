<?php

namespace Kitmap\command\faction\subcommands\admin\power;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use Kitmap\handler\Faction;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;

class Remove extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Main::getInstance(), "remove", "Retire du power à une faction");
        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $faction = strtolower($args["faction"]);
        $amount = intval($args["montant"]);

        if (!Faction::exists($faction)) {
            $sender->sendMessage(Util::PREFIX . "La faction §e" . $faction . " §fn'existe pas");
            return;
        } elseif (0 > $amount) {
            $sender->sendMessage(Util::PREFIX . "Le montant que vous avez inscrit est invalide");
            return;
        }

        Faction::addPower($faction, -$amount);
        $sender->sendMessage(Util::PREFIX . "Vous venez de retirer §e" . $amount . " §fpower(s) à la faction §e" . $faction);
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("faction"));
        $this->registerArgument(1, new IntegerArgument("montant"));
    }
}