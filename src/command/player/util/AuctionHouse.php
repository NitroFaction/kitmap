<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util;

use CortexPE\Commando\BaseCommand;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use muqsit\invmenu\type\InvMenuTypeIds;
use NCore\Base;
use NCore\command\sub\auctionhouse\Sell;
use NCore\handler\Cache;
use NCore\handler\OtherAPI;
use NCore\handler\RankAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\item\ItemBlock;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class AuctionHouse extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "ah",
            "Les commandes relatant à l'hôtel des ventes",
        );

        $this->setAliases(["hdv", "auctionhouse", "market"]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->data["player"]["staff_mod"][0]) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas accèder à l'hotel de vente en étant en staff mod");
                return;
            } else if ($session->inCooldown("combat")) {
                $sender->sendMessage(Util::PREFIX . "Cette commande est interdite en combat");
                return;
            }

            $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
            $menu->setName("Hôtel Des Ventes");

            $page = 1;

            $menu->setListener(InvMenu::readonly(function (DeterministicInvMenuTransaction $transaction) use ($menu, $page): void {
                $player = $transaction->getPlayer();
                $item = $transaction->getItemClicked();

                if (is_null($item->getNamedTag()->getTag("id"))) {
                    $page = $menu->getInventory()->getItem(45)->getCount();

                    if ($item->getCustomName() === "§r§ePage Suivante") {
                        $this->addAuctionHouseItems($menu, ($page + 1));
                    } else if ($item->getCustomName() === "§r§ePage Précédente" && $page > 1) {
                        $this->addAuctionHouseItems($menu, ($page - 1));
                    } else if ($item->getCustomName() === "§r§eRéactualiser") {
                        $this->addAuctionHouseItems($menu, $page);
                    } else if ($item->getCustomName() === "§r§eMes Ventes En Cours") {
                        $this->myItems($player);
                    }
                    return;
                }

                $this->confirm($player, $item, 0);
            }));

            $this->addAuctionHouseItems($menu, $page);
            $menu->send($sender);
        }
    }

    private function addAuctionHouseItems(InvMenu $menu, int $page)
    {
        $menu->getInventory()->clearAll();

        foreach (Util::arrayToPage(array_reverse(Cache::$auctionhouse), $page, 45)[1] as $value) {
            if ($value[1] === "itemBlock") {
                $item = ItemBlock::jsonDeserialize($value[0]);
            } else {
                $item = Item::jsonDeserialize($value[0]);
            }

            $item->getNamedTag()->setInt("menu_item", 0);
            $menu->getInventory()->addItem($item);
        }

        $item = ItemFactory::getInstance()->get(264, 0, $page)->setCustomName("§r§ePage Actuel");
        $menu->getInventory()->setItem(45, $item);

        $item = ItemFactory::getInstance()->get(ItemIds::PAPER)->setCustomName("§r§ePage Précédente");
        $menu->getInventory()->setItem(48, $item);

        $item = ItemFactory::getInstance()->get(368)->setCustomName("§r§eRéactualiser");
        $menu->getInventory()->setItem(49, $item);

        $item = ItemFactory::getInstance()->get(ItemIds::PAPER)->setCustomName("§r§ePage Suivante");
        $menu->getInventory()->setItem(50, $item);

        $item = ItemFactory::getInstance()->get(54)->setCustomName("§r§eMes Ventes En Cours");
        $menu->getInventory()->setItem(53, $item);
    }

    private function myItems(Player $player): void
    {
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        $menu->setName("Hôtel Des Ventes");

        $menu->setListener(InvMenu::readonly(function (DeterministicInvMenuTransaction $transaction): void {
            $player = $transaction->getPlayer();
            $item = $transaction->getItemClicked();

            $this->confirm($player, $item, 1);
        }));

        foreach (self::getAuctionHousePlayerItems($player) as $value) {
            if ($value[1] === "itemBlock") {
                $item = ItemBlock::jsonDeserialize($value[0]);
            } else {
                $item = Item::jsonDeserialize($value[0]);
            }

            $item->getNamedTag()->setInt("menu_item", 0);
            $menu->getInventory()->addItem($item);
        }
        $menu->send($player);
    }

    private function confirm(Player $player, Item $item, int $type): void
    {
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);
        $menu->setName("Hôtel Des Ventes");

        $menu->setListener(InvMenu::readonly(function (DeterministicInvMenuTransaction $transaction) use ($item, $type): void {
            $player = $transaction->getPlayer();

            if ($transaction->getItemClicked()->getCustomName() === "§r§eConfirmer") {
                $this->checkAuctionHouse($player, $item, $type);
            }

            $player->removeCurrentWindow();
        }));

        $confirm = ItemFactory::getInstance()->get(505)->setCustomName("§r§eConfirmer");
        $cancel = ItemFactory::getInstance()->get(503)->setCustomName("§r§eAnnuler");

        foreach ([0, 1, 2, 3, 9, 10, 11, 12, 18, 19, 20, 21] as $slot) $menu->getInventory()->setItem($slot, $confirm);
        foreach ([5, 6, 7, 8, 14, 15, 16, 17, 23, 24, 25, 26] as $slot) $menu->getInventory()->setItem($slot, $cancel);

        $item->getNamedTag()->setInt("menu_item", 0);
        $menu->getInventory()->setItem(13, $item);

        $menu->send($player);
    }

    private function checkAuctionHouse(Player $player, Item $item, int $type): void
    {
        $session = Session::get($player);

        if (is_null($item->getNamedTag()->getTag("id")) || is_null($item->getNamedTag()->getTag("price"))) {
            return;
        }

        $price = $item->getNamedTag()->getInt("price");
        $id = $item->getNamedTag()->getInt("id");
        $seller = strtolower($item->getNamedTag()->getString("seller"));

        if ($price > $session->data["player"]["money"] && $type === 0) {
            $player->sendMessage(Util::PREFIX . "Vous n'avez pas assez d'argent pour acheter cela");
            return;
        } else if (!$player->getInventory()->canAddItem($item)) {
            $player->sendMessage(Util::PREFIX . "Vous n'avez pas assez de place dans votre inventaire");
            return;
        } else if (!isset(Cache::$auctionhouse[$id])) {
            $player->sendMessage(Util::PREFIX . "Cet item n'est plus disponible dans l'hotel des ventes");
            return;
        }

        if ($type === 0) {
            if (!isset(Cache::$players["upper_name"][$seller])) {
                $player->sendMessage(Util::PREFIX . "Une erreur est survenue lors de l'achat de l'item");
                return;
            }

            $target = Base::getInstance()->getServer()->getPlayerByPrefix($seller);

            if ($target instanceof Player) {
                $rank = RankAPI::getEqualRank($target->getName());
                $tax = RankAPI::getRankValue($rank, "tax");

                $_price = $price * (1 - $tax / 100);

                Session::get($target)->addValue("money", $_price);
                $target->sendMessage(Util::PREFIX . "Un joueur vient d'acheter un item à vous dans l'hotel des ventes");
            } else {
                $file = Util::getFile("players/" . $seller);

                $rank = RankAPI::getEqualRank($seller);
                $tax = RankAPI::getRankValue($rank, "tax");

                $_price = $price * (1 - $tax / 100);

                $file->set("money", $file->get("money") + $_price);
                $file->save();
            }

            Base::getInstance()->getLogger()->info("Le joueur " . $player->getName() . " vient d'acheter l'item de " . $seller . ". Prix: " . $price . "; Item: " . $item->getVanillaName());
            $session->addValue("money", $price, true);
        }

        $item->getNamedTag()->removeTag("price");
        $item->getNamedTag()->removeTag("id");
        $item->getNamedTag()->removeTag("seller");
        $item->getNamedTag()->removeTag("menu_item");

        if (($lore = $item->getLore()) >= 5) {
            $item->setLore(array_splice($lore, 0, -5));
        }

        OtherAPI::addItem($player, $item);
        unset(Cache::$auctionhouse[$id]);

        if ($type === 0) {
            $player->sendMessage(Util::PREFIX . "Vous venez d'acheter un item à l'hotel des ventes pour §e" . $price . " §fpièces");
        } else if ($type === 1) {
            $player->sendMessage(Util::PREFIX . "Vous venez de supprimer un de vos items dans l'hotel des ventes");
        }
    }

    public static function getAuctionHousePlayerItems(Player $player): array
    {
        $result = [];

        foreach (Cache::$auctionhouse as $value) {
            if ($value[2] === $player->getXuid()) {
                $result[] = $value;
            }
        }
        return $result;
    }

    protected function prepare(): void
    {
        $this->registerSubCommand(new Sell());
    }
}