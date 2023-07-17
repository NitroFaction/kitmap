<?php

namespace NCore\command\sub\faction\admin\plots;

use CortexPE\Commando\BaseSubCommand;
use NCore\Base;
use NCore\Session;
use pocketmine\command\CommandSender;
use pocketmine\item\ItemFactory;
use pocketmine\player\Player;

class Cancel extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "cancel", "");
        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            Cancel::cancel($sender);
        }
    }

    public static function cancel(Player $player): void
    {
        $item = ItemFactory::getInstance()->get(294);
        $player->getInventory()->remove($item);

        Session::get($player)->data["player"]["plot"] = [false, null, null, false];
    }

    protected function prepare(): void
    {
    }
}