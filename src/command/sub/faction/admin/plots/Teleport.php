<?php

namespace NCore\command\sub\faction\admin\plots;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\BaseSubCommand;
use NCore\Base;
use NCore\handler\Cache;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\world\Position;

class Teleport extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "teleport", "");

        $this->setAliases(["tp"]);
        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $id = $args["id"];
            $data = Cache::$plots[$id];

            $pos = new Position($data["min_x"], 80, $data["min_z"], $sender->getWorld());
            $sender->teleport($pos->add(0.5, 0, 0.5));
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new IntegerArgument("id"));
    }
}