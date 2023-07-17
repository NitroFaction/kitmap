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

class Leader extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "leader", "Définir un nouveau chef de faction");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);
            $faction = $session->data["player"]["faction"];

            if (!FactionAPI::hasFaction($sender)) {
                $sender->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction");
                return;
            } else if (FactionAPI::getFactionRank($sender) !== "leader") {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez définir un nouveau chef seulement si vous en êtes le chef");
                return;
            } else if (!in_array($args["membre"], FactionAPI::getFactionMembers($faction, false))) {
                $sender->sendMessage(Util::PREFIX . "Ce joueur n'est pas dans votre faction (verifiez les majuscules)");
                return;
            } else if ($args["membre"] === $sender->getName()) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas passer de chef à chef");
                return;
            }

            $rank = FactionAPI::getFactionRank($faction, $args["membre"]);
            unset(Cache::$factions[$faction]["members"][$rank . "s"][array_search($args["membre"], Cache::$factions[$faction]["members"][$rank . "s"])]);

            Cache::$factions[$faction]["members"]["officiers"][] = $sender->getName();
            Cache::$factions[$faction]["members"]["leader"] = $args["membre"];

            Cache::$factions[$faction]["logs"][time()] = "§e" . $sender->getName() . " §fdonne son lead a §e" . $args["membre"];
            FactionAPI::broadcastMessage($faction, "§e[§fF§e] §fLe joueur §e" . $args["membre"] . " §fest votre nouveau chef de faction");
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("membre"));
    }
}