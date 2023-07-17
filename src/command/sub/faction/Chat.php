<?php

namespace NCore\command\sub\faction;

use CortexPE\Commando\BaseSubCommand;
use NCore\Base;
use NCore\handler\FactionAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class Chat extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "chat", "Active ou desactive le chat de faction");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if (!FactionAPI::hasFaction($sender)) {
                $sender->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction");
                return;
            }

            if ($session->data["player"]["faction_chat"]) {
                $session->data["player"]["faction_chat"] = false;
                $sender->sendMessage(Util::PREFIX . "Vous venez de de desactiver le chat de faction");
            } else {
                $session->data["player"]["faction_chat"] = true;
                $sender->sendMessage(Util::PREFIX . "Vous venez d'activer le chat de faction");
            }
        }
    }

    protected function prepare(): void
    {
    }
}