<?php

namespace Kitmap\handler;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use OutOfBoundsException;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use Util\util\IdsUtils;

class PackAPI
{
    public static function openPackUI(Player $player): void
    {
        $session = Session::get($player);

        $form = new SimpleForm(function (Player $player, mixed $data) use ($session) {
            if (!is_int($data)) {
                return;
            }

            switch ($data) {
                case 0:
                    if (0 >= $session->data["pack"]) {
                        $player->sendMessage(Util::PREFIX . "Vous ne possedez pas de pack actuellement");
                        return;
                    }

                    self::openPack($player);
                    break;
                case 1:
                    self::buyPack($player);
                    break;
                case 2:
                    self::previsualizePack($player);
                    break;
            }
        });
        $form->setTitle("Pack");
        $form->setContent(Util::PREFIX . "Vous possedez actuellement §e" . $session->data["pack"] . " §fpack(s)");
        $form->addButton("Ouvrir un pack");
        $form->addButton("Acheter un pack");
        $form->addButton("Visualiser les lots");
        $player->sendForm($form);
    }

    public static function openPack(Player $player): void
    {
        $session = Session::get($player);

        if (0 >= $session->data["pack"]) {
            $player->sendMessage(Util::PREFIX . "Vous ne possedez pas de pack actuellement");
            return;
        }

        $items = self::getRandomItems(3);
        $prize = "";

        foreach ($items as $item) {
            if ($item instanceof Item) {
                $prize .= $item->getVanillaName() . ", ";
                OtherAPI::addItem($player, $item);
            }
        }

        Main::getInstance()->getLogger()->info("Le joueur " . $player->getName() . " vient d'ouvrir un pack (ses lots: " . $prize . ")");
        Main::getInstance()->getServer()->broadcastTip(Util::PREFIX . "Le joueur §e" . $player->getName() . " §fvient d'ouvrir un pack !");

        $player->sendMessage(Util::PREFIX . "Vous venez d'ouvrir un pack ! Vos lots ont été mis dans votre inventaire");
        $session->addValue("pack", -1);
    }

    public static function getRandomItems(int $count): array
    {
        $pack = self::getItems();
        $items = [];

        foreach (self::arrayRandom($pack, $count) as $item) {
            if ($item instanceof Item) {
                if (!is_null($item->getNamedTag()->getTag("menu_item"))) {
                    $item->getNamedTag()->removeTag("menu_item");
                }

                if (!is_null($item->getNamedTag()->getTag("type")) && !is_null($item->getNamedTag()->getTag("data"))) {
                    $type = $item->getNamedTag()->getInt("type");
                    $data = $item->getNamedTag()->getInt("data");

                    if ($type === 3) {
                        if (!is_null($item->getNamedTag()->getTag("type"))) {
                            $item->getNamedTag()->removeTag("type");
                        } elseif (!is_null($item->getNamedTag()->getTag("data"))) {
                            $item->getNamedTag()->removeTag("data");
                        }

                        $item->setCount($data);
                        $item->removeEnchantment(VanillaEnchantments::PROTECTION());
                    }
                }

                $items[] = $item;
            }
        }
        return $items;
    }

    public static function getItems(): array
    {
        $items = [];

        foreach (array_keys(Cache::$config["partneritems"]) as $pp) {
            if ($pp === "pumpkinaxe") {
                $items[] = PartnerItemsAPI::createItem($pp)->setCount(2);
                continue;
            }

            $items[] = PartnerItemsAPI::createItem($pp)->setCount(4);
        }

        $items[] = self::initializeItem(ItemFactory::getInstance()->get(ItemIds::PAPER), ["§r§bKit Champion", 1, 1]);
        $items[] = self::initializeItem(ItemFactory::getInstance()->get(ItemIds::PAPER), ["§r§bKit Prince", 1, 2]);
        $items[] = self::initializeItem(ItemFactory::getInstance()->get(ItemIds::PAPER), ["§r§bKit Elite", 1, 3]);
        $items[] = self::initializeItem(ItemFactory::getInstance()->get(ItemIds::PAPER), ["§r§bKit Elite", 1, 3]);
        $items[] = self::initializeItem(ItemFactory::getInstance()->get(ItemIds::PAPER), ["§r§bKit Roi", 1, 4]);
        $items[] = self::initializeItem(ItemFactory::getInstance()->get(ItemIds::PAPER), ["§r§bKit Roi", 1, 4]);
        $items[] = self::initializeItem(ItemFactory::getInstance()->get(ItemIds::PAPER), ["§r§eBillet de 1k", 0, 1000]);
        $items[] = self::initializeItem(ItemFactory::getInstance()->get(ItemIds::PAPER), ["§r§eBillet de 10k", 0, 10000]);
        $items[] = self::initializeItem(ItemFactory::getInstance()->get(ItemIds::PAPER), ["§r§eBillet de 30k", 0, 30000]);
        $items[] = self::initializeItem(ItemFactory::getInstance()->get(ItemIds::PAPER), ["§r§e1 Pack", 2, 1]);
        $items[] = ItemFactory::getInstance()->get(IdsUtils::IRIS_FRAGMENT)->setCount(1);
        $items[] = ItemFactory::getInstance()->get(IdsUtils::IRIS_FRAGMENT)->setCount(2);
        $items[] = ItemFactory::getInstance()->get(IdsUtils::IRIS_FRAGMENT)->setCount(3);
        $items[] = ItemFactory::getInstance()->get(IdsUtils::IRIS_FRAGMENT)->setCount(4);
        $items[] = ItemFactory::getInstance()->get(IdsUtils::IRIS_FRAGMENT)->setCount(4);
        $items[] = ItemFactory::getInstance()->get(IdsUtils::IRIS_FRAGMENT)->setCount(5);
        $items[] = ItemFactory::getInstance()->get(IdsUtils::IRIS_INGOT);
        $items[] = ItemFactory::getInstance()->get(IdsUtils::ILVAITE_PICKAXE);
        $items[] = ItemFactory::getInstance()->get(IdsUtils::FARM_HELMET);
        $items[] = ItemFactory::getInstance()->get(IdsUtils::FARM_CHESTPLATE);
        $items[] = ItemFactory::getInstance()->get(IdsUtils::FARM_LEGGINGS);
        $items[] = ItemFactory::getInstance()->get(IdsUtils::FARM_BOOTS);
        $items[] = ItemFactory::getInstance()->get(ItemIds::EMERALD)->setCount(16);
        $items[] = ItemFactory::getInstance()->get(ItemIds::EMERALD)->setCount(32);
        $items[] = ItemFactory::getInstance()->get(ItemIds::EMERALD)->setCount(64);
        $items[] = ItemFactory::getInstance()->get(ItemIds::EMERALD_BLOCK)->setCount(8);
        $items[] = ItemFactory::getInstance()->get(ItemIds::EMERALD_BLOCK)->setCount(16);
        $items[] = ItemFactory::getInstance()->get(ItemIds::EXPERIENCE_BOTTLE)->setCount(16);
        $items[] = ItemFactory::getInstance()->get(ItemIds::EXPERIENCE_BOTTLE)->setCount(32);
        $items[] = ItemFactory::getInstance()->get(ItemIds::EXPERIENCE_BOTTLE)->setCount(64);
        $items[] = ItemFactory::getInstance()->get(IdsUtils::EMERALD_SWORD);
        $items[] = ItemFactory::getInstance()->get(IdsUtils::IRIS_SWORD);
        $items[] = ItemFactory::getInstance()->get(IdsUtils::FARMING_CHEST);
        $items[] = ItemFactory::getInstance()->get(IdsUtils::ANTIBACK_BLOCK)->setCount(32);
        $items[] = ItemFactory::getInstance()->get(IdsUtils::ANTIBACK_BLOCK)->setCount(64);
        $items[] = ItemFactory::getInstance()->get(IdsUtils::GlASS_ANTIBACK_BLOCK, IdsUtils::GlASS_ANTIBACK_META)->setCount(32);
        $items[] = ItemFactory::getInstance()->get(ItemIds::DRAGON_BREATH);
        $items[] = ItemFactory::getInstance()->get(ItemIds::NETHERSTAR);

        foreach ($items as $item) {
            $item->getNamedTag()->setInt("menu_item", 0);
        }

        return $items;
    }

    public static function initializeItem(Item $item, array $itemData): Item
    {
        $customName = $itemData[0];

        if (!is_null($customName)) {
            $item->setCustomName($customName);
        }

        $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 10));

        $item->getNamedTag()->setInt("type", $itemData[1]);
        $item->getNamedTag()->setInt("data", $itemData[2]);

        return $item;
    }

    private static function arrayRandom(array $array, int $n = 1): array
    {
        if ($n < 1 || $n > count($array)) {
            throw new OutOfBoundsException();
        }

        return ($n !== 1) ? array_values(array_intersect_key($array, array_flip(array_rand($array, $n)))) : array($array[array_rand($array)]);
    }

    private static function buyPack(Player $player): void
    {
        $session = Session::get($player);

        $form = new CustomForm(function (Player $player, mixed $data) use ($session) {
            if (!is_array($data) || !isset($data[1]) || !isset($data[2]) || !is_bool($data[2]) || !$data[2]) {
                return;
            }

            switch ($data[1]) {
                case 0:
                    if (75 > $session->data["gem"]) {
                        $player->sendMessage(Util::PREFIX . "Vous ne possedez pas assez de gemmes pour acheter un pack");
                        return;
                    }

                    $session->addValue("gem", 75, true);
                    $player->sendMessage(Util::PREFIX . "Vous venez d'acheter un pack avec §e75 §fgemmes");

                    Main::getInstance()->getLogger()->info("Le joueur " . $player->getName() . " vient d'acheter un pack avec des pièces");
                    break;
                case 1:
                    if (100000 > $session->data["money"]) {
                        $player->sendMessage(Util::PREFIX . "Vous ne possedez pas assez de pièces pour acheter un pack");
                        return;
                    }

                    $session->addValue("money", 100000, true);
                    $player->sendMessage(Util::PREFIX . "Vous venez d'acheter un pack avec §e100k §fpièces");

                    Main::getInstance()->getLogger()->info("Le joueur " . $player->getName() . " vient d'acheter un pack avec des gemmes");
                    break;
                default:
                    return;
            }

            $session->addValue("pack", 1);
            self::openPackUI($player);
        });

        $form->setTitle("Pack");
        $form->addLabel(Util::PREFIX . "Êtes vous sur d'acheter un pack?\nPrix d'un pack: §e100k §fpièces ou §a75 §fgemmes\n\nVous possedez §e" . $session->data["gem"] . " §fgemme(s)\nVous possedez §e" . $session->data["money"] . " §fpièces(s)\n");
        $form->addDropdown("Méthode de payement", ["gemmes", "pièces"]);
        $form->addToggle("Acheter un pack?", true);
        $player->sendForm($form);
    }

    private static function previsualizePack(Player $player): void
    {
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        $menu->setName("Prévisualisation des packs");
        $menu->setListener(InvMenu::readonly());

        foreach (self::getItems() as $key => $item) {
            if ($item instanceof Item) {
                $menu->getInventory()->setItem($key, $item);
            }
        }

        $menu->send($player);
    }

    public static function executeInteractPackItem(Player $player, PlayerItemUseEvent $event): bool
    {
        $item = $player->getInventory()->getItemInHand();
        if (is_null($item->getNamedTag()->getTag("type")) || is_null($item->getNamedTag()->getTag("data"))) {
            return false;
        }

        $type = $item->getNamedTag()->getInt("type");
        $data = $item->getNamedTag()->getInt("data");

        $session = Session::get($player);

        switch ($type) {
            case 0:
                $session->addValue("money", $data);

                Main::getInstance()->getLogger()->info("Le joueur " . $player->getName() . " vient d'utiliser un billet de " . $data . " pièces");
                $player->sendMessage(Util::PREFIX . "Vous venez d'utiliser un billet et recevoir §e" . $data . " §fpièces");
                break;
            case 1:
                $name = match ($data) {
                    1 => "champion",
                    2 => "prince",
                    3 => "elite",
                    4 => "roi",
                    default => "joueur"
                };

                Util::executeCommand("givekit \"" . $player->getName() . "\" " . $name);
                break;
            case 2:
                $session->addValue("pack", $data);

                Main::getInstance()->getLogger()->info("Le joueur " . $player->getName() . " vient d'utiliser un papier de " . $data . " pack");
                $player->sendMessage(Util::PREFIX . "Vous venez de recevoir §e" . $data . " §fpack(s)");
                break;
        }

        $item->pop();
        $player->getInventory()->setItemInHand($item->isNull() ? ItemFactory::air() : $item);

        $event->cancel();
        return true;
    }
}