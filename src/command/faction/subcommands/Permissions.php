<?php

namespace Kitmap\command\faction\subcommands;

use jojoe77777\FormAPI\CustomForm;
use Kitmap\command\faction\FactionCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\Faction;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Permissions extends FactionCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "permissions",
            "Classement des meilleurs factions"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
        $this->setAliases(["perms"]);
    }

    public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args): void
    {
        $permissions = Cache::$factions[$faction]["permissions"];
        $names = Cache::$config["permissions"];

        $form = new CustomForm(function (Player $player, mixed $data) use ($faction) {
            if (!is_array($data)) {
                return;
            } elseif (!Faction::exists($faction)) {
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
            $form->addDropdown($description, array_values(Cache::$config["faction_ranks"]), Faction::getRankPosition($actual), $permission);
        }

        $sender->sendForm($form);
    }

    protected function prepare(): void
    {
    }
}