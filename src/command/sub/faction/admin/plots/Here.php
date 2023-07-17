<?php

namespace NCore\command\sub\faction\admin\plots;

use CortexPE\Commando\BaseSubCommand;
use NCore\Base;
use NCore\handler\FactionAPI;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class Here extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "here", "");
        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $plot = FactionAPI::inPlot($sender->getPosition()->getX(), $sender->getPosition()->getZ());
            $sender->sendMessage(Util::PREFIX . "Vous êtes dans le plot §e" . $plot[2] . "§f, la faction possèdant le claim est la §e" . $plot[1]);
        }
    }

    protected function prepare(): void
    {
    }
}