<?php

namespace NCore\command\sub\faction\admin;

use CortexPE\Commando\BaseSubCommand;
use NCore\Base;
use NCore\command\sub\faction\admin\power\Add;
use NCore\command\sub\faction\admin\power\Remove;
use pocketmine\command\CommandSender;

class Power extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "power", "Retire du power à une faction");
        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
    }

    protected function prepare(): void
    {
        $this->registerSubCommand(new Add());
        $this->registerSubCommand(new Remove());
    }
}