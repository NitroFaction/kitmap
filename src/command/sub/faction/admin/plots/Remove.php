<?php

namespace NCore\command\sub\faction\admin\plots;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\BaseSubCommand;
use NCore\Base;
use NCore\handler\Cache;
use NCore\Util;
use pocketmine\command\CommandSender;

class Remove extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "remove", "");
        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $id = $args["id"];

        if (isset(Cache::$plots[$id])) {
            unset(Cache::$plots[$id]);
            $sender->sendMessage(Util::PREFIX . "Vous venez de supprimer le plot §e" . $id);
        } else {
            $sender->sendMessage(Util::PREFIX . "Le plot avec l'id §e" . $id . " §fn'éxiste pas");
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new IntegerArgument("id"));
    }
}