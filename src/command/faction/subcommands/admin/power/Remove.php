<?php

namespace Kitmap\command\faction\subcommands\admin\power;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use Kitmap\handler\Faction;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;

class Remove extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Main::getInstance(), "remove", "Retire du power à une faction");
        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $faction = strtolower($args["faction"]);
        $amount = intval($args["montant"]);

        if (!Faction::exists($faction)) {
            $sender->sendMessage(Util::PREFIX . "La faction §6" . $faction . " §fn'existe pas");
            return;
        } else if (0 > $amount) {
            $sender->sendMessage(Util::PREFIX . "Le montant que vous avez inscrit est invalide");
            return;
        }

        Faction::addPower($faction, -$amount);
        $sender->sendMessage(Util::PREFIX . "Vous venez de retirer §6" . $amount . " §fpower(s) à la faction §6" . $faction);
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("faction"));
        $this->registerArgument(1, new IntegerArgument("montant"));
    }
}