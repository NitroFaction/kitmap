<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff;

use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Rank;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\block\utils\DyeColor;
use pocketmine\command\CommandSender;
use pocketmine\item\VanillaItems;
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
        $this->setPermissions([Rank::GROUP_STAFF]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            if (Session::get($sender)->data["staff_mod"][0]) {
                $item = $sender->getInventory()->getItemInHand();

                if ($item->getCustomName() === "§r" . Util::PREFIX . "Spectateur §9§l«" && $item === VanillaItems::BANNER()->setColor(DyeColor::LIGHT_GRAY())) {
                    $sender->getInventory()->setItemInHand(VanillaItems::BANNER()->setColor(DyeColor::GREEN())->setCustomName("§r" . Util::PREFIX . "Spectateur §9§l«"));
                } else if ($item->getCustomName() === "§r" . Util::PREFIX . "Spectateur §9§l«" && VanillaItems::BANNER()->setColor(DyeColor::GREEN())) {
                    $sender->getInventory()->setItemInHand(VanillaItems::BANNER()->setColor(DyeColor::LIGHT_GRAY())->setCustomName("§r" . Util::PREFIX . "Spectateur §9§l«"));
                }
            }

            if ($sender->getGamemode() === GameMode::SPECTATOR()) {
                $sender->setGamemode(GameMode::SURVIVAL());
                $sender->sendMessage(Util::PREFIX . "Vous n'êtes plus en mode spectateur");

                if (Session::get($sender)->data["staff_mod"][0]) {
                    $sender->setAllowFlight(true);
                }
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