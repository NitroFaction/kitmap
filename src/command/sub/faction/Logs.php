<?php

namespace NCore\command\sub\faction;

use CortexPE\Commando\BaseSubCommand;
use jojoe77777\FormAPI\SimpleForm;
use NCore\Base;
use NCore\handler\Cache;
use NCore\handler\FactionAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class Logs extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "logs", "Récupére les logs de votre faction");
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
            }

            self::showLogsForm($sender, $faction);
        }
    }

    public static function showLogsForm(Player $player, string $faction): void
    {
        $logs = Cache::$factions[$faction]["logs"] ?? [];
        $content = "";

        foreach ($logs as $key => $value) {
            $content .= "§e" . date("d-m h:i", $key) . "§f: " . $value . "\n";
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