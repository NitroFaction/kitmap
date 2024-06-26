<?php

namespace Kitmap\command\util\market;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\BaseSubCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\Rank;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\item\VanillaItems;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class MarketSellSub extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "sell",
            "Met l'item de sa main à l'hotel de vente"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);
            $rank = Rank::getEqualRank($sender->getName());

            if (count(Market::getAuctionHousePlayerItems($sender)) === Rank::getRankValue($rank, "hdv")) {
                $sender->sendMessage(Util::PREFIX . "Vous avez vendu trop d'item dans l'hôtel des ventes");
                return;
            } else if (0 >= $args["prix"]) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas envoyer un montant négatif");
                return;
            } else if ($args["prix"] >= 10000000) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas vendre si cher votre item");
                return;
            } else if ($session->inCooldown("combat")) {
                $sender->sendMessage(Util::PREFIX . "Cette commande est interdite en combat");
                return;
            }

            $item = $sender->getInventory()->getItemInHand();

            if ($item->equals(VanillaItems::AIR())) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas vendre de l'air");
                return;
            }

            while (true) {
                $id = rand(1, 99999);

                if (!isset(Cache::$market[$id])) {
                    break;
                }
            }

            $item->setLore(array_merge($item->getLore(), [
                "§r§f ",
                "§r§fVendeur: §9" . $sender->getName(),
                "§r§fPrix: §9" . $args["prix"] . "$",
                "§r§9 ",
                "§r§9Cliquez ici pour acheter",
            ]));

            $item->getNamedTag()->setString("seller", $sender->getName());
            $item->getNamedTag()->setString("price", strval($args["prix"]));
            $item->getNamedTag()->setString("id", strval($id));

            Cache::$market[$id] = Market::serialize($item, $sender->getName());

            $sender->getInventory()->setItemInHand(VanillaItems::AIR());
            $sender->sendMessage(Util::PREFIX . "Vous venez de vendre l'item dans votre main au prix de §9" . $args["prix"]);
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new IntegerArgument("prix"));
    }
}