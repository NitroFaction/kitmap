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

class Withdraw extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "withdraw", "Retirer l'argent de la banque de faction", ["w", "retirer"]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);
            $permission = FactionAPI::hasPermission($sender, $this->getName());

            if (is_null($permission)) {
                $sender->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction");
                return;
            } else if (!$permission) {
                $sender->sendMessage(Util::PREFIX . "Vous ne possèdez pas les permissions necessaire dans votre faction pour faire cela");
                return;
            }

            $faction = $session->data["player"]["faction"];
            $amount = $args["montant"];

            if (0 >= $amount) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas retirer un montant négatif");
                return;
            } else if (floor($amount) > Cache::$factions[$faction]["money"]) {
                $sender->sendMessage(Util::PREFIX . "L'argent dans la banque de faction est infèrieur à §e" . floor($amount));
                return;
            }

            Cache::$factions[$faction]["money"] -= floor($amount);
            Cache::$factions[$faction]["logs"][time()] = "§e" . $sender->getName() . " §fretire §e" . $amount . " §fde la banque";

            $session->addValue("money", floor($amount));
            FactionAPI::broadcastMessage($faction, "§e[§fF§e] §e" . $sender->getName() . " §fvient de retirer §e" . floor($amount) . " §fpièces de la banque de faction");
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new IntegerArgument("montant"));
    }
}