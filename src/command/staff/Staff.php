<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff;

use CortexPE\Commando\BaseCommand;
use Kitmap\command\staff\tool\Vanish;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Staff extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "staff",
            "Active ou désactive le mode staff"
        );

        $this->setPermission("staff.group");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);
            $data = $session->data["staff_mod"];

            if (in_array($sender->getName(), Vanish::$vanish)) {
                $session->removeCooldown("cmd");
                $sender->chat("/vanish");
            }

            if (!$data[0]) {
                $armor = $items = [];

                foreach ($sender->getArmorInventory()->getContents() as $slot => $item) $armor[$slot] = $item->jsonSerialize();
                foreach ($sender->getInventory()->getContents() as $slot => $item) $items[$slot] = $item->jsonSerialize();

                if ($sender->getGamemode() === GameMode::SPECTATOR()) {
                    $sender->setGamemode(GameMode::SURVIVAL());
                }

                $xp = $sender->getXpManager()->getCurrentTotalXp();
                $gamemode = $sender->getGamemode()->getEnglishName();

                $session->data["staff_mod"] = [true, [
                    "armor" => $armor,
                    "items" => $items,
                    "xp" => $xp,
                    "gm" => $gamemode
                ]];

                $sender->sendMessage(Util::PREFIX . "Vous venez d'activer le staff mod");
                $this->sendItems($sender);
            } else {
                $sender->getArmorInventory()->clearAll();
                $sender->getInventory()->clearAll();

                foreach ($data[1]["armor"] as $slot => $item) $sender->getArmorInventory()->setItem($slot, Item::jsonDeserialize($item));
                foreach ($data[1]["items"] as $slot => $item) $sender->getInventory()->setItem($slot, Item::jsonDeserialize($item));

                if ($sender->getGamemode() === GameMode::SPECTATOR() || $sender->getGamemode() === GameMode::SURVIVAL()) {
                    $sender->setGamemode(GameMode::SURVIVAL());
                    $sender->setAllowFlight(false);
                    $sender->setFlying(false);
                }

                $sender->getXpManager()->setCurrentTotalXp($data[1]["xp"]);
                $sender->setGamemode(GameMode::fromString($data[1]["gm"]));

                $session->data["staff_mod"] = [false, []];
                $sender->sendMessage(Util::PREFIX . "Vous venez de désactiver le staff mod");
            }
        }
    }

    private function sendItems(Player $player): void
    {
        $player->setAllowFlight(true);
        $player->getArmorInventory()->clearAll();

        $player->getInventory()->clearAll();
        $player->getXpManager()->setXpLevel(0);

        $knockback = new EnchantmentInstance(VanillaEnchantments::KNOCKBACK(), 2);

        $player->getInventory()->setItem(0, ItemFactory::getInstance()->get(507)->setCustomName("§r" . Util::PREFIX . "Spectateur §e§l«"));
        $player->getInventory()->setItem(2, ItemFactory::getInstance()->get(341)->setCustomName("§r" . Util::PREFIX . "Knockback 2 §e§l«")->addEnchantment($knockback));
        $player->getInventory()->setItem(3, ItemFactory::getInstance()->get(339)->setCustomName("§r" . Util::PREFIX . "Alias §e§l«"));
        $player->getInventory()->setItem(4, ItemFactory::getInstance()->get(381)->setCustomName("§r" . Util::PREFIX . "Random Tp §e§l«"));
        $player->getInventory()->setItem(5, ItemFactory::getInstance()->get(369)->setCustomName("§r" . Util::PREFIX . "Freeze §e§l«"));
        $player->getInventory()->setItem(6, ItemFactory::getInstance()->get(280)->setCustomName("§r" . Util::PREFIX . "Sanction §e§l«"));
        $player->getInventory()->setItem(7, ItemFactory::getInstance()->get(54)->setCustomName("§r" . Util::PREFIX . "Invsee §e§l«"));
        $player->getInventory()->setItem(8, ItemFactory::getInstance()->get(130)->setCustomName("§r" . Util::PREFIX . "Ecsee §e§l«"));

        if (in_array($player->getName(), Vanish::$vanish)) {
            $player->getInventory()->setItem(1, ItemFactory::getInstance()->get(351, 10)->setCustomName("§r" . Util::PREFIX . "Vanish §e§l«"));
        } else {
            $player->getInventory()->setItem(1, ItemFactory::getInstance()->get(351, 8)->setCustomName("§r" . Util::PREFIX . "Vanish §e§l«"));
        }
    }

    protected function prepare(): void
    {
    }
}