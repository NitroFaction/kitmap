<?php

namespace NCore\command\sub\faction;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use NCore\Base;
use NCore\handler\Cache;
use NCore\handler\FactionAPI;
use NCore\handler\RankAPI;
use NCore\handler\ScoreFactory;
use NCore\Session;
use NCore\task\repeat\event\OutpostTask;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class Rename extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "rename", "Renomme sa faction");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);
            $name = strtolower($args["nom"]);

            $permission = FactionAPI::hasPermission($sender, $this->getName());

            if (is_null($permission)) {
                $sender->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction");
                return;
            } else if (!$permission) {
                $sender->sendMessage(Util::PREFIX . "Vous ne possèdez pas les permissions necessaire dans votre faction pour faire cela");
                return;
            } else if (FactionAPI::exist($name)) {
                $sender->sendMessage(Util::PREFIX . "Ce nom de faction existe déjà");
                return;
            } else if (!ctype_alnum($name) || strlen($name) > 16) {
                $sender->sendMessage(Util::PREFIX . "Ce nom de faction est invalide ou trop long");
                return;
            } else if ($session->inCooldown("faction_rename")) {
                $sender->sendMessage(Util::PREFIX . "Vous essayez de renommer votre faction trop rapidement");
                return;
            }

            $faction = $session->data["player"]["faction"];
            $data = Cache::$factions[$faction];

            $data["upper_name"] = $args["nom"];

            $island = "island-";
            Cache::$factions[$name] = $data;

            foreach (FactionAPI::getFactionMembers($faction, false) as $player) {
                $target = Base::getInstance()->getServer()->getPlayerByPrefix($player);

                if ($target instanceof Player) {
                    Session::get($target)->data["player"]["faction"] = $name;

                    RankAPI::updateNameTag($target);
                    ScoreFactory::updateScoreboard($target);
                } else {
                    $username = strtolower($player);
                    $file = Util::getFile("players/" . $username);

                    if ($file->getAll() !== []) {
                        $file->set("faction", $name);
                        $file->save();
                    }
                }
            }

            if (!is_null(Cache::$factions[$faction]["claim"])) {
                $plot = Cache::$factions[$faction]["claim"];
                Cache::$plots[$plot]["faction"] = $name;
            }

            if (Cache::$dynamic["outpost"] === $faction) Cache::$dynamic["outpost"] = $name;
            if (OutpostTask::$currentFaction === $faction) OutpostTask::$currentFaction = $name;

            FactionAPI::renameWorld($island . $faction, $island . $name);
            unset(Cache::$factions[$faction]);

            Cache::$factions[$name]["logs"][time()] = "§e" . $sender->getName() . " §frenome la faction §e" . $name;

            $sender->sendMessage(Util::PREFIX . "Vous venez de renommer votre faction §e" . $faction . " §fen §e" . $name);
            $session->setCooldown("faction_rename", (60 * 5));
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("nom"));
    }
}