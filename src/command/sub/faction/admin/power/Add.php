<?php

namespace NCore\command\sub\faction\admin\power;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use NCore\Base;
use NCore\handler\FactionAPI;
use NCore\Util;
use pocketmine\command\CommandSender;

class Add extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "add", "Ajoute du power à une faction");
        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $faction = strtolower($args["faction"]);
        $amount = intval($args["montant"]);

        if (!FactionAPI::exist($faction)) {
            $sender->sendMessage(Util::PREFIX . "La faction §e" . $faction . " §fn'existe pas");
            return;
        } else if (0 > $amount) {
            $sender->sendMessage(Util::PREFIX . "Le montant que vous avez inscrit est invalide");
            return;
        }

        FactionAPI::addPower($faction, $amount);
        $sender->sendMessage(Util::PREFIX . "Vous venez d'ajouter §e" . $amount . " §fpower(s) à la faction §e" . $faction);
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("faction"));
        $this->registerArgument(1, new IntegerArgument("montant"));
    }
}