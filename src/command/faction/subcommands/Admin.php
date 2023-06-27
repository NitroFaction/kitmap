<?php

namespace Kitmap\command\faction\subcommands;

use CortexPE\Commando\BaseSubCommand;
use Kitmap\command\faction\subcommands\admin\Power;
use Kitmap\Main;
use pocketmine\command\CommandSender;

class Admin extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Main::getInstance(), "admin", "Permet de gérer toutes les factions");
        $this->setPermission("pocketmine.group.operator");
        // TODO COMMANDE FACTION ADMIN
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {

    }

    protected function prepare(): void
    {
        $this->registerSubCommand(new \Kitmap\command\faction\subcommands\admin\Delete());
        $this->registerSubCommand(new \Kitmap\command\faction\subcommands\admin\Leader());
        $this->registerSubCommand(new \Kitmap\command\faction\subcommands\admin\Logs());

        $this->registerSubCommand(new Power());
    }
}