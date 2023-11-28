<?php

namespace Kitmap\command\util\market;

use CortexPE\Commando\BaseSubCommand;
use Kitmap\handler\Cache;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\item\VanillaItems;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class MarketAverageSub extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "moyenne",
            "Donne la moyenne du prix des 5 derniers achat de l'item dans sa main"
        );

        $this->setAliases(["average"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $item = $sender->getInventory()->getItemInHand();

            if ($item->equals(VanillaItems::AIR())) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas vendre de l'air à l'hôtel des ventes");
                return;
            }

            $avg = Cache::$data["average"][$item->getVanillaName()] ?? [];

            if (count($avg) === 0) {
                $sender->sendMessage(Util::PREFIX . "L'item dans votre main n'a jamais été vendu à l'hôtel des ventes");
                return;
            }

            $prices = array_slice($avg, -5);
            $average = array_sum($prices) / count($prices);

            $sender->sendMessage(Util::PREFIX . "L'item dans votre main est vendu à §q" . Util::formatNumberWithSuffix($average) . " §fpièces en moyenne à l'hôtel des ventes");
        }
    }

    protected function prepare(): void
    {
    }
}