<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff;

use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Rank;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\command\CommandSender;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\VanillaItems;
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

        $this->setPermissions([Rank::GROUP_STAFF]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);
            $data = $session->data["staff_mod"];

            if (!$data[0]) {
                if ($sender->getGamemode() === GameMode::SPECTATOR()) {
                    $sender->setGamemode(GameMode::SURVIVAL());
                }

                $session->data["staff_mod"] = [true, Util::savePlayerData($sender)];

                $sender->sendMessage(Util::PREFIX . "Vous venez d'activer le staff mod");
                $this->sendItems($sender);
            } else {
                Util::restorePlayer($sender, $data[1]);

                $session->data["staff_mod"] = [false, []];
                $sender->sendMessage(Util::PREFIX . "Vous venez de désactiver le staff mod");

                if (in_array($sender->getName(), Vanish::$vanish)) {
                    $sender->sendMessage(Util::PREFIX . "Vous restez cependant toujours en vanish, n'oubliez pas de l'enlever");
                }

                if (!$sender->isCreative()) {
                    $sender->setAllowFlight(false);
                    $sender->setFlying(false);
                }
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

        $player->getInventory()->setItem(0, VanillaItems::BANNER()->setColor(DyeColor::LIGHT_GRAY())->setCustomName("§r" . Util::PREFIX . "Spectateur §9§l«"));
        $player->getInventory()->setItem(2, VanillaItems::SLIMEBALL()->setCustomName("§r" . Util::PREFIX . "Knockback 2 §9§l«")->addEnchantment($knockback));
        $player->getInventory()->setItem(3, VanillaItems::PAPER()->setCustomName("§r" . Util::PREFIX . "Alias §9§l«"));
        $player->getInventory()->setItem(4, VanillaItems::SPIDER_EYE()->setCustomName("§r" . Util::PREFIX . "Random Tp §9§l«"));
        $player->getInventory()->setItem(5, VanillaItems::BLAZE_ROD()->setCustomName("§r" . Util::PREFIX . "Freeze §9§l«"));
        $player->getInventory()->setItem(6, VanillaItems::STICK()->setCustomName("§r" . Util::PREFIX . "Sanction §9§l«"));
        $player->getInventory()->setItem(7, VanillaBlocks::CHEST()->asItem()->setCustomName("§r" . Util::PREFIX . "Invsee §9§l«"));
        $player->getInventory()->setItem(8, VanillaBlocks::ENDER_CHEST()->asItem()->setCustomName("§r" . Util::PREFIX . "Ecsee §9§l«"));

        if (in_array($player->getName(), Vanish::$vanish)) {
            $player->getInventory()->setItem(1, VanillaItems::DYE()->setColor(DyeColor::GREEN())->setCustomName("§r" . Util::PREFIX . "Vanish §9§l«"));
        } else {
            $player->getInventory()->setItem(1, VanillaItems::DYE()->setColor(DyeColor::LIGHT_GRAY())->setCustomName("§r" . Util::PREFIX . "Vanish §9§l«"));
        }
    }

    protected function prepare(): void
    {
    }
}