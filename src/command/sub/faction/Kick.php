<?php

namespace NCore\command\sub\faction;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use NCore\Base;
use NCore\handler\Cache;
use NCore\handler\FactionAPI;
use NCore\handler\RankAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class Kick extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "kick", "Expulser un joueur de sa faction");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);
            $faction = $session->data["player"]["faction"];

            $permission = FactionAPI::hasPermission($sender, $this->getName());

            if (is_null($permission)) {
                $sender->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction");
                return;
            } else if (!$permission) {
                $sender->sendMessage(Util::PREFIX . "Vous ne possèdez pas les permissions necessaire dans votre faction pour faire cela");
                return;
            } else if (!in_array($args["membre"], FactionAPI::getFactionMembers($faction, false))) {
                $sender->sendMessage(Util::PREFIX . "Ce joueur n'est pas dans votre faction (verifiez les majuscules)");
                return;
            }

            $sender_rank = FactionAPI::getFactionRank($sender);
            $target_rank = FactionAPI::getFactionRank($faction, $args["membre"]);

            $sender_position = FactionAPI::getRankPosition($sender_rank);
            $target_position = FactionAPI::getRankPosition($target_rank);

            if (!($target_position > $sender_position)) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas expulser un joueur qui a votre rang ou si il a un meilleur rang que vous");
                return;
            }

            $target = Base::getInstance()->getServer()->getPlayerByPrefix($args["membre"]);

            if ($target instanceof Player) {
                $targetSession = Session::get($target);

                $targetSession->data["player"]["faction"] = null;
                $targetSession->data["player"]["faction_chat"] = false;

                RankAPI::updateNameTag($target);
            }

            Cache::$factions[$faction]["logs"][time()] = "§e" . $sender->getName() . " §fkick §e" . $args["membre"];

            unset(Cache::$factions[$faction]["members"][$target_rank . "s"][array_search($args["membre"], Cache::$factions[$faction]["members"][$target_rank . "s"])]);
            FactionAPI::broadcastMessage($faction, "§e[§fF§e] §fLe joueur §e" . $args["membre"] . " §fvient d'être expulsé de votre faction");
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("membre"));
    }
}