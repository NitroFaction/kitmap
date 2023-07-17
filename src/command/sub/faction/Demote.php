<?php

namespace NCore\command\sub\faction;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use NCore\Base;
use NCore\handler\Cache;
use NCore\handler\FactionAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class Demote extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "demote", "Rétrograde un membre de sa faction");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);
            $faction = $session->data["player"]["faction"];

            $sender_rank = FactionAPI::getFactionRank($sender);

            if (is_null($sender_rank)) {
                $sender->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction");
                return;
            } else if (!in_array($args["membre"], FactionAPI::getFactionMembers($faction, false))) {
                $sender->sendMessage(Util::PREFIX . "Ce joueur n'est pas dans votre faction (verifiez les majuscules)");
                return;
            } else if ($sender->getName() === $args["membre"]) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas vous rétograder vous même");
                return;
            }

            $target_rank = FactionAPI::getFactionRank($faction, $args["membre"]);
            $previous_rank = FactionAPI::getPreviousRank($target_rank);

            $sender_position = FactionAPI::getRankPosition($sender_rank);
            $target_position = FactionAPI::getRankPosition($target_rank);

            if (!($target_position > $sender_position)) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas rétrograder un joueur qui a votre rang ou si il a un meilleur rang que vous");
                return;
            }

            $rank_name = Cache::$config["faction_ranks"][$previous_rank];

            unset(Cache::$factions[$faction]["members"][$target_rank . "s"][array_search($args["membre"], Cache::$factions[$faction]["members"][$target_rank . "s"])]);
            Cache::$factions[$faction]["members"][$previous_rank . "s"][] = $args["membre"];

            Cache::$factions[$faction]["logs"][time()] = "§e" . $sender->getName() . " §fdemote §e" . $args["membre"] . "§f" . $rank_name;
            FactionAPI::broadcastMessage($faction, "§e[§fF§e] §fLe joueur §e" . $args["membre"] . " §fvient d'être rétogradé §e" . $rank_name);
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("membre"));
    }
}