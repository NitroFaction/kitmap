<?php /** @noinspection PhpUnused */

namespace NCore\command\staff\tool;

use CortexPE\Commando\BaseCommand;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\item\ItemFactory;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Spec extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "spec",
            "Change de mode de jeu"
        );

        $this->setAliases(["spectate"]);
        $this->setPermission("staff.group");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player && Session::get($sender)->data["player"]["staff_mod"][0]) {
            $item = $sender->getInventory()->getItemInHand();

            if ($item->getCustomName() === "§r" . Util::PREFIX . "Spectateur §e§l«" && $item->getId() === 507) {
                $sender->getInventory()->setItemInHand(ItemFactory::getInstance()->get(501)->setCustomName("§r" . Util::PREFIX . "Spectateur §e§l«"));
            } else if ($item->getCustomName() === "§r" . Util::PREFIX . "Spectateur §e§l«" && $item->getId() === 501) {
                $sender->getInventory()->setItemInHand(ItemFactory::getInstance()->get(507)->setCustomName("§r" . Util::PREFIX . "Spectateur §e§l«"));
            }

            if ($sender->getGamemode() === GameMode::SPECTATOR()) {
                $sender->setGamemode(GameMode::SURVIVAL());
                $sender->setAllowFlight(true);
                $sender->sendMessage(Util::PREFIX . "Vous n'êtes plus en mode spectateur");
            } else {
                $sender->setGamemode(GameMode::SPECTATOR());
                $sender->sendMessage(Util::PREFIX . "Vous êtes désormais en mode spectateur");
            }
        }
    }

    protected function prepare(): void
    {
    }
}