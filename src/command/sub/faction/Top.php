<?php

namespace NCore\command\sub\faction;

use CortexPE\Commando\BaseSubCommand;
use NCore\Base;
use NCore\Session;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class Top extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "top", "Classement des meilleurs factions");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            Session::get($sender)->removeCooldown("cmd");
            $sender->chat("/top faction");
        }
    }

    protected function prepare(): void
    {
    }
}