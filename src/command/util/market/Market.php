<?php /** @noinspection PhpUnused */

namespace Kitmap\command\util\market;

use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\Rank;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Market extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "market",
            "Les commandes relatant à l'hôtel des ventes",
        );

        $this->setAliases(["hdv", "auctionhouse", "ah"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public static function serialize(Item $item, string $seller = null): string
    {
        $nbt = CompoundTag::create()->setTag("Item", new ListTag([
            $item->nbtSerialize()
        ], NBT::TAG_Compound));

        if (!is_null($seller)) {
            $nbt = $nbt->setString("Seller", $seller);
        }

        return Util::serializeCompoundTag($nbt);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->data["staff_mod"][0]) {
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

                    if ($item->getCustomName() === "§r§9Page Suivante") {
                        $this->addAuctionHouseItems($menu, ($page + 1));
                    } else if ($item->getCustomName() === "§r§9Page Précédente" && $page > 1) {
                        $this->addAuctionHouseItems($menu, ($page - 1));
                    } else if ($item->getCustomName() === "§r§9Réactualiser") {
                        $this->addAuctionHouseItems($menu, $page);
                    } else if ($item->getCustomName() === "§r§9Mes Ventes En Cours") {
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

    private function addAuctionHouseItems(InvMenu $menu, int $page): void
    {
        $menu->getInventory()->clearAll();

        foreach (Util::arrayToPage(array_reverse(Cache::$market), $page, 45)[1] as $data) {
            $nbt = self::deserialize($data);
            $item = self::readItem($nbt);

            $item->getNamedTag()->setInt("menu_item", 0);
            $menu->getInventory()->addItem($item);
        }

        $item = VanillaItems::DIAMOND()->setCount($page)->setCustomName("§r§9Page Actuel");
        $menu->getInventory()->setItem(45, $item);

        $item = VanillaItems::PAPER()->setCustomName("§r§9Page Précédente");
        $menu->getInventory()->setItem(48, $item);

        $item = VanillaItems::ENDER_PEARL()->setCustomName("§r§9Réactualiser");
        $menu->getInventory()->setItem(49, $item);

        $item = VanillaItems::PAPER()->setCustomName("§r§9Page Suivante");
        $menu->getInventory()->setItem(50, $item);

        $item = VanillaBlocks::CHEST()->asItem()->setCustomName("§r§9Mes Ventes En Cours");
        $menu->getInventory()->setItem(53, $item);
    }

    public static function deserialize(string $contents): CompoundTag
    {
        return Util::deserializePlayerData("static deserialize()", $contents);
    }

    public static function readItem(CompoundTag $nbt): Item
    {
        $items = $nbt->getListTag("Item");

        if ($items !== null) {
            /** @var CompoundTag $item */
            foreach ($items->getIterator() as $item) {
                return Item::nbtDeserialize($item);
            }
        }
        return VanillaItems::AIR();
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

        foreach (self::getAuctionHousePlayerItems($player) as $id => $value) {
            $item = self::readItem($value);

            $item->getNamedTag()->setString("id", $id);
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

            if ($transaction->getItemClicked()->getCustomName() === "§r§aConfirmer") {
                $this->checkAuctionHouse($player, $item, $type);
            }

            Util::removeCurrentWindow($player);
        }));

        $confirm = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::GREEN())->asItem()->setCustomName("§r§aConfirmer");
        $cancel = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::RED())->asItem()->setCustomName("§r§cAnnuler");

        foreach ([0, 1, 2, 3, 9, 10, 11, 12, 18, 19, 20, 21] as $slot) $menu->getInventory()->setItem($slot, $confirm);
        foreach ([5, 6, 7, 8, 14, 15, 16, 17, 23, 24, 25, 26] as $slot) $menu->getInventory()->setItem($slot, $cancel);

        $item->getNamedTag()->setInt("menu_item", 0);
        $menu->getInventory()->setItem(13, $item);

        $menu->send($player);
    }

    private function checkAuctionHouse(Player $player, Item $item, int $type): void
    {
        $session = Session::get($player);

        $id = $item->getNamedTag()->getTag("id");
        $price = $item->getNamedTag()->getTag("price");

        if (is_null($id) || is_null($price)) {
            return;
        }

        $seller = $this->cleanNameTag($item->getNamedTag()->getString("seller"));

        $id = intval($this->cleanNameTag($id->getValue()));
        $price = intval($this->cleanNameTag($price->getValue()));
        $seller = strtolower($seller);

        if ($price > $session->data["money"] && $type === 0) {
            $player->sendMessage(Util::PREFIX . "Vous n'avez pas assez d'argent pour acheter cela");
            return;
        } else if (!$player->getInventory()->canAddItem($item)) {
            $player->sendMessage(Util::PREFIX . "Vous n'avez pas assez de place dans votre inventaire");
            return;
        } else if (!isset(Cache::$market[$id])) {
            $player->sendMessage(Util::PREFIX . "Cet item n'est plus disponible dans l'hotel des ventes");
            return;
        }

        if ($type === 0) {
            if (!isset(Cache::$players["upper_name"][$seller])) {
                $player->sendMessage(Util::PREFIX . "Une erreur est survenue lors de l'achat de l'item");
                return;
            }

            $target = Main::getInstance()->getServer()->getPlayerExact($seller);

            if ($target instanceof Player) {
                $rank = Rank::getEqualRank($target->getName());
                $tax = Rank::getRankValue($rank, "tax");

                $_price = $price * (1 - $tax / 100);

                Session::get($target)->addValue("money", $_price);
                $target->sendMessage(Util::PREFIX . "Un joueur vient d'acheter un item à vous dans l'hotel des ventes");
            } else {
                $file = Util::getFile("data/players/" . $seller);

                $rank = Rank::getEqualRank($seller);
                $tax = Rank::getRankValue($rank, "tax");

                $_price = $price * (1 - $tax / 100);

                $file->set("money", $file->get("money") + $_price);
                $file->save();
            }

            if (!isset(Cache::$data["average"][$item->getVanillaName()])) {
                Cache::$data["average"][$item->getVanillaName()] = [];
            }

            Cache::$data["average"][$item->getVanillaName()][] = $price;
            Main::getInstance()->getLogger()->info("Le joueur " . $player->getName() . " vient d'acheter l'item de " . $seller . ". Prix: " . $price . "; Item: " . $item->getVanillaName());

            $session->addValue("money", $price, true);
        }

        $item->getNamedTag()->removeTag("price");
        $item->getNamedTag()->removeTag("id");
        $item->getNamedTag()->removeTag("seller");
        $item->getNamedTag()->removeTag("menu_item");

        if (($lore = $item->getLore()) >= 5) {
            $item->setLore(array_splice($lore, 0, -5));
        }

        Util::addItem($player, $item);
        unset(Cache::$market[$id]);

        if ($type === 0) {
            $player->sendMessage(Util::PREFIX . "Vous venez d'acheter un item à l'hotel des ventes pour §9" . $price . " §fpièces");
        } else if ($type === 1) {
            $player->sendMessage(Util::PREFIX . "Vous venez de supprimer un de vos items dans l'hotel des ventes");
        }
    }

    private function cleanNameTag(mixed $input): string
    {
        return str_replace(["\"", "tag_int", "tag_string"], ["", "", ""], strval($input));
    }

    public static function getAuctionHousePlayerItems(Player $player): array
    {
        $result = [];

        foreach (Cache::$market as $id => $data) {
            $nbt = self::deserialize($data);

            if ($nbt->getString("Seller") === $player->getName()) {
                $result[$id] = $nbt;
            }
        }
        return $result;
    }

    protected function prepare(): void
    {
        $this->registerSubCommand(new MarketAverageSub());
        $this->registerSubCommand(new MarketSellSub());
    }
}