<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\faction;

use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use NCore\handler\Cache;
use NCore\handler\OtherAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\item\ItemFactory;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Shop extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "shop",
            "Le marché"
        );
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            if (Session::get($sender)->inCooldown("combat")) {
                $sender->sendMessage(Util::PREFIX . "Cette commande est interdite en combat");
                return;
            }

            $this->categoryForm($sender);
        }
    }

    private function categoryForm(Player $player): void
    {
        $form = new SimpleForm(function (Player $player, mixed $data) {
            if (!is_string($data)) {
                return;
            }

            $this->listMoneyForm($player, $data);
        });

        foreach (Cache::$config["shop"] as $item => $value) {
            $form->addButton($item, -1, "", $item);
        }

        $form->setTitle("Boutique");
        $form->setContent(Util::PREFIX . "Cliquez sur le boutton de votre choix");
        $player->sendForm($form);
    }

    private function listMoneyForm(Player $player, string $category): void
    {
        $form = new SimpleForm(function (Player $player, mixed $data) {
            if (!is_string($data)) {
                return;
            }

            $this->itemMenuForm($player, $data);
        });

        $form->setTitle("Boutique");
        $form->setContent(Util::PREFIX . "Cliquez sur le boutton de votre choix");

        $category = Cache::$config["shop"][$category];
        $items = ($category["type"] === "bourse") ? Cache::$dynamic["bourse"] : $category["items"];

        foreach ($items as $item) {
            list($name, $id, $meta, $buy) = explode(":", $item);

            $form->addButton(
                $name . "\nPrix: §e" . $buy . " §8pièces§e/u",
                0,
                "textures/ids/" . $id . "-" . $meta,
                $item
            );
        }
        $player->sendForm($form);
    }

    private function itemMenuForm(Player $player, string $item): void
    {
        $item = explode(":", $item);
        $customName = $item[5] ?? null;

        list($name, $id, $meta, $buy, $sell) = $item;
        $limit = (($items = OtherAPI::getItemCount($player, $id, $meta)) > 256) ? OtherAPI::getItemCount($player, $id, $meta) : 256;

        $form = new CustomForm(function (Player $player, mixed $data) use ($name, $id, $meta, $sell, $buy, $customName) {
            if (!is_array($data)) {
                return;
            } else if (1 > ($count = $data[2])) {
                return;
            }

            $session = Session::get($player);

            if ($data[1] === 0) {
                $money = $session->data["player"]["money"];
                $item = ItemFactory::getInstance()->get($id, $meta, $count);

                if ($buy * $count > $money) {
                    $player->sendMessage(Util::PREFIX . "Vous n'avez pas assez d'argent");
                    return;
                } else if (!$player->getInventory()->canAddItem($item)) {
                    $player->sendMessage(Util::PREFIX . "Vous n'avez pas assez de place dans votre inventaire");
                    return;
                }

                if (!is_null($customName)) {
                    $item->setCustomName($customName);
                }

                $session->addValue("money", $buy * $count, true);
                OtherAPI::addItem($player, $item);

                $player->sendMessage(Util::PREFIX . "Vous venez d'acheter §e" . $count . " §f" . $name . " pour §e" . ($buy * $count) . " §fpièces");
            } else {
                if ($count > OtherAPI::getItemCount($player, $id, $meta)) {
                    $player->sendMessage(Util::PREFIX . "Vous n'avez pas assez d'item dans votre inventaire");
                    return;
                }

                $session->addValue("money", $sell * $count);
                $player->getInventory()->removeItem(ItemFactory::getInstance()->get($id, $meta, $count));

                $player->sendMessage(Util::PREFIX . "Vous venez de vendre §e" . $count . " §f" . $name . " pour §e" . ($sell * $count) . " §fpièces");
            }
        });
        $form->setTitle("Boutique");
        $form->addLabel("Nombre de §e" . $name . " §rdans votre inventaire: §e" . $items . "\n\n§fPrix achat unité: §e" . $buy . "\n§fPrix vente unité: §e" . $sell);
        $form->addDropdown("Voulez vous achetez ou vendre", ["Acheter", "Vendre"]);
        $form->addSlider("Combien voulez vous en acheter/vendre?", 1, $limit);
        $player->sendForm($form);
    }

    protected function prepare(): void
    {
    }
}