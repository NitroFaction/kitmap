<?php

namespace NCore\command\sub\faction;

use CortexPE\Commando\BaseSubCommand;
use NCore\Base;
use NCore\command\sub\faction\admin\Plots;
use NCore\command\sub\faction\admin\Power;
use pocketmine\command\CommandSender;

class Admin extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "admin", "Permet de gérer toutes les factions");
        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {

    }

    protected function prepare(): void
    {
        $this->registerSubCommand(new \NCore\command\sub\faction\admin\Delete());
        $this->registerSubCommand(new \NCore\command\sub\faction\admin\Leader());
        $this->registerSubCommand(new \NCore\command\sub\faction\admin\Logs());

        $this->registerSubCommand(new Plots());
        $this->registerSubCommand(new Power());
    }
}