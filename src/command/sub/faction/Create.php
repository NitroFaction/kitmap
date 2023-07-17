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

class Create extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "create", "Créer sa faction", ["make"]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);
            $name = strtolower($args["nom"]);

            if (FactionAPI::hasFaction($sender)) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas recréer une faction si vous en avez déjà une");
                return;
            } else if (FactionAPI::exist($name)) {
                $sender->sendMessage(Util::PREFIX . "Ce nom de faction existe déjà");
                return;
            } else if (!ctype_alnum($name) || strlen($name) > 16) {
                $sender->sendMessage(Util::PREFIX . "Ce nom de faction est invalide ou trop long");
                return;
            }

            Cache::$factions[$name] = [
                "upper_name" => $args["nom"],
                "home" => "0:0:0",
                "claim" => null,
                "members" => [
                    "leader" => $sender->getName(),
                    "officiers" => [],
                    "members" => [],
                    "recruits" => []
                ],
                "permissions" => [
                    "delete" => "leader",
                    "withdraw" => "leader",

                    "expand" => "officier",
                    "set-spawn" => "officier",
                    "delete-island" => "officier",
                    "lock" => "officier",
                    "invite" => "officier",
                    "kick" => "officier",
                    "sethome" => "officier",
                    "delhome" => "officier",
                    "claim" => "officier",
                    "unclaim" => "officier",
                    "logs" => "officier",
                    "rename" => "officier",

                    "place" => "member",
                    "break" => "member",
                    "chest" => "member",
                    "time" => "member",

                    "fence-gates" => "recruit",
                    "trapdoor" => "recruit",
                    "door" => "recruit",
                    "home" => "recruit",
                    "island" => "recruit"
                ],
                "activity" => [],
                "power" => 0,
                "money" => 0,
                "island" => [
                    "lock" => false,
                    "zone" => [
                        "min" => 256,
                        "max" => 271
                    ]
                ]
            ];

            $session->data["player"]["faction"] = $name;

            $sender->sendMessage(Util::PREFIX . "Vous venez de créer votre faction §e" . $args["nom"] . " §f!");
            RankAPI::updateNameTag($sender);
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("nom"));
    }
}