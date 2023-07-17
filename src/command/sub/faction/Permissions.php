<?php

namespace NCore\command\sub\faction;

use CortexPE\Commando\BaseSubCommand;
use jojoe77777\FormAPI\CustomForm;
use NCore\Base;
use NCore\handler\Cache;
use NCore\handler\FactionAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class Permissions extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "permissions", "Classement des meilleurs factions", ["perms"]);
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
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez changer les permissions de la faction que en étant chef");
                return;
            }

            $permissions = Cache::$factions[$faction]["permissions"];
            $names = Cache::$config["permissions"];

            $form = new CustomForm(function (Player $player, mixed $data) use ($faction) {
                if (!is_array($data)) {
                    return;
                } else if (!FactionAPI::exist($faction)) {
                    $player->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction ou la faction a été renommé");
                    return;
                }

                foreach ($data as $key => $value) {
                    if (isset(Cache::$factions[$faction]["permissions"][$key])) {
                        $rank = array_keys(Cache::$config["faction_ranks"])[$value] ?? "recruit";
                        Cache::$factions[$faction]["permissions"][$key] = $rank;
                    }
                }

                $player->sendMessage(Util::PREFIX . "Vous venez de mettre à jour les permissions de votre faction");
            });

            $form->setTitle("Permissions");
            $form->addLabel(Util::PREFIX . "Choissisez un rôle minimum pour faire des actions:");

            foreach ($names as $permission => $description) {
                $actual = $permissions[$permission];
                $form->addDropdown($description, array_values(Cache::$config["faction_ranks"]), FactionAPI::getRankPosition($actual), $permission);
            }

            $sender->sendForm($form);
        }
    }

    protected function prepare(): void
    {
    }
}