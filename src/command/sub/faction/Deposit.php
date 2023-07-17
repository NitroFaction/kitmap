<?php

namespace NCore\command\sub\faction;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\BaseSubCommand;
use NCore\Base;
use NCore\handler\Cache;
use NCore\handler\FactionAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class Deposit extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "deposit", "Déposer de l'argent dans la banque de faction", ["d"]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if (!FactionAPI::hasFaction($sender)) {
                $sender->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction");
                return;
            }

            $faction = $session->data["player"]["faction"];
            $amount = $args["montant"];

            if (0 >= $amount) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas envoyer un montant négatif");
                return;
            } else if (floor($amount) > $session->data["player"]["money"]) {
                $sender->sendMessage(Util::PREFIX . "Votre monnaie est infèrieur à §e" . floor($amount));
                return;
            }

            Cache::$factions[$faction]["money"] += floor($amount);
            Cache::$factions[$faction]["logs"][time()] = "§e" . $sender->getName() . " §fdepose §e" . $amount . " §fdans la banque";

            $session->addValue("money", floor($amount), true);

            FactionAPI::broadcastMessage($faction, "§e[§fF§e] §e" . $sender->getName() . " §fvient d'ajouter §e" . floor($amount) . " §fpièces dans la banque de faction");
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new IntegerArgument("montant"));
    }
}