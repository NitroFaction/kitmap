<?php /** @noinspection PhpUnused */

namespace NCore\command\staff\server;

use CortexPE\Commando\BaseCommand;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class ItemId extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "floating",
            "Fait disparaitre ou apparaitre les floatings texts"
        );

        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $item = $sender->getInventory()->getItemInHand();

            $sender->sendMessage(Util::PREFIX . "Id: " . $item->getId());
            $sender->sendMessage(Util::PREFIX . "Meta: " . $item->getMeta());
            $sender->sendMessage(Util::PREFIX . "Name: " . $item->getName());
        }
    }

    protected function prepare(): void
    {
    }
}