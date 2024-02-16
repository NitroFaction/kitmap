<?php

namespace Kitmap\handler;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;

class Pack
{
    public static function openPackUI(Player $player): void
    {
        $form = new SimpleForm(function (Player $player, mixed $data) {
            if (!is_string($data)) {
                return;
            }

            self::openPackCategoryUI($player, $data);
        });
        $form->setTitle("Pack");
        $form->setContent(Util::PREFIX . "Quel genre de pack voulez vous ouvrir");
        foreach (Cache::$config["packs"] as $key => $value) {
            $form->addButton("Pack " . $key, label: $key);
        }
        $player->sendForm($form);
    }

    public static function openPackCategoryUI(Player $player, string $category): void
    {
        $session = Session::get($player);

        $form = new SimpleForm(function (Player $player, mixed $data) use ($session, $category) {
            if (!is_int($data)) {
                return;
            }

            switch ($data) {
                case 0:
                    if (0 >= $session->data["packs"][$category]) {
                        $player->sendMessage(Util::PREFIX . "Vous ne possedez pas de pack actuellement");
                        return;
                    }

                    self::openPack($player, $category);
                    break;
                case 1:
                    self::buyPack($player, $category);
                    break;
                case 2:
                    self::previsualizePack($player, $category);
                    break;
            }
        });
        $form->setTitle("Pack " . $category);
        $form->setContent(Util::PREFIX . "Vous possedez actuellement §9" . $session->data["packs"][$category] . " §fpack(s) §9" . $category);
        $form->addButton("Ouvrir un pack " . $category);
        $form->addButton("Acheter un pack " . $category);
        $form->addButton("Visualiser les lots");
        $player->sendForm($form);
    }

    public static function openPack(Player $player, string $category): void
    {
        $session = Session::get($player);

        if (0 >= $session->data["packs"]) {
            $player->sendMessage(Util::PREFIX . "Vous ne possedez pas de pack actuellement");
            return;
        }

        $items = self::getRandomItems(Cache::$config["packs"][$category]["items-count"], $category);
        $prize = "";

        foreach ($items as $item) {
            if ($item instanceof Item) {
                $prize .= $item->getVanillaName() . ", ";
                Util::addItem($player, $item);
            }
        }

        Main::getInstance()->getLogger()->info("Le joueur " . $player->getName() . " vient d'ouvrir un pack " . $category . " (ses lots: " . $prize . ")");
        Main::getInstance()->getServer()->broadcastTip(Util::PREFIX . "Le joueur §9" . $player->getName() . " §fvient d'ouvrir un pack §9" . $category . " §f!");

        $player->sendMessage(Util::PREFIX . "Vous venez d'ouvrir un pack §9" . $category . " §f! Vos lots ont été mis dans votre inventaire");
        $session->addValue(["packs", $category], 1, true);
    }

    public static function getRandomItems(int $count, $category): array
    {
        $pack = self::getItems($category);
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
                        } else if (!is_null($item->getNamedTag()->getTag("data"))) {
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

    public static function getItems(string $category): array
    {
        $items = [];

        $config = Cache::$config["packs"][$category]["items"];

        if ($config[0] === "partneritems") {
            foreach (array_keys(Cache::$config["partneritems"]) as $pp) {
                if ($pp === "pumpkinaxe") {
                    $items[] = PartnerItems::createItem($pp)->setCount(2);
                    continue;
                }

                $items[] = PartnerItems::createItem($pp)->setCount(4);
            }

            array_shift($config);
        }

        foreach ($config as $item) {
            $item = explode(":", $item);

            if ($item[0] === "paper") {
                $items[] = self::initializeItem(VanillaItems::PAPER(), [$item[1], $item[2], $item[3]]);
            } else if ($item[0] === "item") {
                $items[] = Util::getItemByName($item[1])->setCount(intval($item[2]));
            }
        }

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
        return ($n !== 1) ? array_values(array_intersect_key($array, array_flip(array_rand($array, $n)))) : [$array[array_rand($array)]];
    }

    private static function buyPack(Player $player, string $category): void
    {
        $session = Session::get($player);
        $prices = Cache::$config["packs"][$category]["prices"];

        $form = new CustomForm(function (Player $player, mixed $data) use ($session, $category, $prices) {
            if (!is_array($data) || !isset($data[1]) || !isset($data[2]) || !is_bool($data[2]) || !$data[2]) {
                return;
            }

            switch ($data[1]) {
                case 0:
                    if ($prices["gem"] > $session->data["gem"]) {
                        $player->sendMessage(Util::PREFIX . "Vous ne possedez pas assez de gemmes pour acheter un pack §9" . $category);
                        return;
                    }

                    $session->addValue("gem", 75, true);
                    $player->sendMessage(Util::PREFIX . "Vous venez d'acheter un pack §9" . $category . "avec §9" . Util::formatNumberWithSuffix($prices["gem"]) . " §fgemmes");

                    Main::getInstance()->getLogger()->info("Le joueur " . $player->getName() . " vient d'acheter un pack " . $category . " avec des gemmes");
                    break;
                case 1:
                    if ($prices["money"] > $session->data["money"]) {
                        $player->sendMessage(Util::PREFIX . "Vous ne possedez pas assez de pièces pour acheter un pack §9" . $category);
                        return;
                    }

                    $session->addValue("money", 100000, true);
                    $player->sendMessage(Util::PREFIX . "Vous venez d'acheter un pack §9" . $category . " §favec §9" . Util::formatNumberWithSuffix($prices["money"]) . " §fpièces");

                    Main::getInstance()->getLogger()->info("Le joueur " . $player->getName() . " vient d'acheter un pack " . $category . " avec des pièces");
                    break;
                default:
                    return;
            }

            $session->addValue(["packs", $category], 1);
            self::openPackCategoryUI($player, $category);
        });
        $form->setTitle("Pack " . $category);
        $form->addLabel(Util::PREFIX . "Êtes vous sur d'acheter un pack " . $category . "?\nPrix d'un pack: §9" . Util::formatNumberWithSuffix($prices["money"]) . " §fpièces ou §a" . Util::formatNumberWithSuffix($prices["gem"]) . " §fgemmes\n\nVous possedez §9" . $session->data["gem"] . " §fgemme(s)\nVous possedez §9" . $session->data["money"] . " §fpièces(s)\n");
        $form->addDropdown("Méthode de payement", ["gemmes", "pièces"]);
        $form->addToggle("Acheter un pack?", true);
        $player->sendForm($form);
    }

    private static function previsualizePack(Player $player, string $category): void
    {
        $length = count(Cache::$config["packs"][$category]["items"]);

        if ($length > 27) {
            $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        } else {
            $menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);
        }

        $menu->setName("Lots possible du pack " . $category);
        $menu->setListener(InvMenu::readonly());

        foreach (self::getItems($category) as $key => $item) {
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
                $player->sendMessage(Util::PREFIX . "Vous venez d'utiliser un billet et recevoir §9" . $data . " §fpièces");
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
            case 3:
                $session->addValue("gem", $data);

                Main::getInstance()->getLogger()->info("Le joueur " . $player->getName() . " vient d'utiliser un billet de " . $data . " gemmes");
                $player->sendMessage(Util::PREFIX . "Vous venez d'utiliser un billet et recevoir §9" . $data . " §fgemmes");
                break;
        }

        $item->pop();
        $player->getInventory()->setItemInHand($item->isNull() ? VanillaItems::AIR() : $item);

        $event->cancel();
        return true;
    }
}