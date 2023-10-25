<?php

namespace Kitmap\command\faction\subcommands;

use jojoe77777\FormAPI\SimpleForm;
use Kitmap\command\faction\FactionCommand;
use Kitmap\handler\Cache;
use Kitmap\Main;
use Kitmap\Session;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Logs extends FactionCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "logs",
            "Récupére les logs de votre faction"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args): void
    {
        self::showLogsForm($sender, $faction);
    }

    public static function showLogsForm(Player $player, string $faction): void
    {
        $logs = Cache::$factions[$faction]["logs"] ?? [];
        $content = "";

        foreach ($logs as $key => $value) {
            $content .= "§6" . date("d-m H:i", $key) . "§f: " . $value . "\n";
        }

        $form = new SimpleForm(null);
        $form->setTitle("Logs de faction");
        $form->setContent($content);
        $player->sendForm($form);
    }

    protected function prepare(): void
    {
    }
}