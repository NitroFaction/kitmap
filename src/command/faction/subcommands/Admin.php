<?php

namespace Kitmap\command\faction\subcommands;

use CortexPE\Commando\BaseSubCommand;
use Kitmap\command\faction\subcommands\admin\Claim;
use Kitmap\command\faction\subcommands\admin\Delete;
use Kitmap\command\faction\subcommands\admin\Leader;
use Kitmap\command\faction\subcommands\admin\Logs;
use Kitmap\command\faction\subcommands\admin\Power;
use Kitmap\command\faction\subcommands\admin\Unclaim;
use Kitmap\Main;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;

class Admin extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Main::getInstance(), "admin", "Permet de gérer toutes les factions");
        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
    }

    protected function prepare(): void
    {
        $this->registerSubCommand(new Claim());
        $this->registerSubCommand(new Delete());
        $this->registerSubCommand(new Leader());
        $this->registerSubCommand(new Power());
        $this->registerSubCommand(new Logs());
        $this->registerSubCommand(new Unclaim());
    }
}