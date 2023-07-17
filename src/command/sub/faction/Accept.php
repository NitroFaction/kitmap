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

class Accept extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "accept", "Accepter une invitation de la part d'une faction", ["join"]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if (FactionAPI::hasFaction($sender)) {
                $sender->sendMessage(Util::PREFIX . "Vous appartenez déjà à une faction");
                return;
            }

            if (!isset($args["faction"])) {
                if (count($session->data["player"]["invite"]) === 0) {
                    $sender->sendMessage(Util::PREFIX . "Vous ne possèdez aucune invitation pour rejoindre une faction");
                    return;
                } else if (count($session->data["player"]["invite"]) != 1) {
                    $sender->sendMessage(Util::PREFIX . "Vous possèdez plus qu'une invitation de faction, merci d'écrire la faction que vous voulez rejoindre");
                    return;
                }

                $faction = $session->data["player"]["invite"][0];
                $this->join($faction, $sender);
            } else {
                $members = FactionAPI::getFactionMembers(strtolower($args["faction"]), false);

                if (!in_array(strtolower($args["faction"]), $session->data["player"]["invite"])) {
                    $sender->sendMessage(Util::PREFIX . "Vous n'avez aucune invitation provenant de cette faction");
                    return;
                } else if (count($members) >= 20) {
                    $sender->sendMessage(Util::PREFIX . "Cette faction ne peut pas comporter plus de 20 joueurs");
                    return;
                }

                $this->join(strtolower($args["faction"]), $sender);
            }

            RankAPI::updateNameTag($sender);
        }
    }

    private function join(string $faction, Player $player): void
    {
        $session = Session::get($player);

        if (!FactionAPI::exist($faction)) {
            $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas accepter une invitation d'une faction inexistante");
            return;
        }

        Cache::$factions[$faction]["members"]["recruits"][] = $player->getName();
        Cache::$factions[$faction]["logs"][time()] = "§e" . $player->getName() . " §fa rejoint la faction";

        $session->data["player"]["faction"] = $faction;
        $session->data["player"]["invite"] = [];

        FactionAPI::broadcastMessage($faction, "§e[§fF§e] §fLe joueur §e" . $player->getName() . " §fvient de rejoindre votre faction");
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("faction", true));
    }
}