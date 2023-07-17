<?php

namespace NCore\command\sub\auctionhouse;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\BaseSubCommand;
use NCore\Base;
use NCore\command\player\util\AuctionHouse;
use NCore\handler\Cache;
use NCore\handler\RankAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\item\ItemBlock;
use pocketmine\item\ItemFactory;
use pocketmine\player\Player;

class Sell extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "sell", "Met l'item de sa main à l'hotel de vente");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);
            $rank = RankAPI::getEqualRank($sender->getName());

            if (count(AuctionHouse::getAuctionHousePlayerItems($sender)) === RankAPI::getRankValue($rank, "hdv")) {
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

            if ($item->getId() === 0) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas vendre de l'air");
                return;
            }

            while (true) {
                $id = rand(1, 99999);

                if (!isset(Cache::$auctionhouse[$id])) {
                    break;
                }
            }

            $item->setLore(array_merge($item->getLore(), [
                "§r§f ",
                "§r§fVendeur: §e" . $sender->getName(),
                "§r§fPrix: §e" . $args["prix"] . "$",
                "§r§e ",
                "§r§eCliquez ici pour acheter",
            ]));

            $item->getNamedTag()->setString("seller", $sender->getName());
            $item->getNamedTag()->setInt("price", $args["prix"]);
            $item->getNamedTag()->setInt("id", $id);

            if ($item instanceof ItemBlock) {
                Cache::$auctionhouse[$id] = [$item->jsonSerialize(), "itemBlock", $sender->getXuid()];
            } else if ($item instanceof Item) {
                Cache::$auctionhouse[$id] = [$item->jsonSerialize(), "item", $sender->getXuid()];
            }

            $sender->getInventory()->setItemInHand(ItemFactory::air());
            $sender->sendMessage(Util::PREFIX . "Vous venez de vendre l'item dans votre main au prix de §e" . $args["prix"]);
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new IntegerArgument("prix"));
    }
}