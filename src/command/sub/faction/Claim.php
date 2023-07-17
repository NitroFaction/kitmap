<?php

namespace NCore\command\sub\faction;

use CortexPE\Commando\BaseSubCommand;
use NCore\Base;
use NCore\handler\Cache;
use NCore\handler\FactionAPI;
use NCore\handler\OtherAPI;
use NCore\handler\RankAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class Claim extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "claim", "Claim une zone spécifique");
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
            } else if (!is_null(Cache::$factions[$faction]["claim"])) {
                $sender->sendMessage(Util::PREFIX . "Vous avez déjà un claim. Faites §e/f unclaim§f pour le supprimer");
                return;
            }

            $plot = FactionAPI::inPlot($sender->getPosition()->getX(), $sender->getPosition()->getZ());

            if ($plot[1] !== "Aucune faction") {
                $sender->sendMessage(Util::PREFIX . "Ce claim n'est pas libre");
                return;
            } else if (OtherAPI::insideZone($sender->getPosition(), "vip_claim") && !RankAPI::hasRank($sender, "champion")) {
                $sender->sendMessage(Util::PREFIX . "Ce claim est spécifique aux joueurs ayant un grade, demandez à quelqu'un avec un grade de votre faction de claim ici pour vous");
                return;
            }

            Cache::$factions[$faction]["claim"] = $plot[2];
            Cache::$plots[$plot[2]]["faction"] = $faction;

            Cache::$factions[$faction]["logs"][time()] = "§e" . $sender->getName() . " §fa récupéré un nouveau claim";
            FactionAPI::broadcastMessage($faction, "§e[§fF§r§e] §fVotre faction vient de récuperer un nouveau claim");
        }
    }

    protected function prepare(): void
    {
    }
}