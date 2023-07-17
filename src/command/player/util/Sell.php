<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use NCore\handler\Cache;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Sell extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "sell",
            "Vendre ses items automatiquement au shop"
        );
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if (isset($args["opt"]) && $args["opt"] === "all") {
                $category = $args["categorie"] ?? false;
                $total = 0;

                foreach ($sender->getInventory()->getContents() as $index => $item) {
                    $sell = $this->sellItem($item, $category);

                    if ($sell[0]) {
                        $total += $sell[2] * $item->getCount();
                        $sender->getInventory()->setItem($index, ItemFactory::air());
                    }
                }

                $session->addValue("money", $total);
                $sender->sendMessage(Util::PREFIX . "Tous les items vendable de votre inventaire ont été vendu, cela vous a rapporté §e" . $total . " §fpièces");
                return;
            }

            $index = $sender->getInventory()->getHeldItemIndex();
            $item = $sender->getInventory()->getItem($index);

            $sell = $this->sellItem($item, false);

            if (!$sell[0]) {
                $sender->sendMessage(Util::PREFIX . "L'item dans votre main ne peut pas être vendu");
            } else {
                $sender->getInventory()->setItem($index, ItemFactory::air());

                $total = ($item->getCount() * $sell[2]);
                $session->addValue("money", $total);

                $sender->sendMessage(Util::PREFIX . "Vous venez de vendre §e" . $item->getCount() . " " . $sell[1] . " §fpour §e" . $total . " §fpièces");
            }
        }
    }

    private function sellItem(Item $item, mixed $category): array
    {
        $sellPrice = 0;
        $itemName = "";

        if (!is_null($item->getNamedTag()->getTag("partneritem"))) {
            return [false];
        }

        foreach (Cache::$config["shop"] as $key => $value) {
            if (!is_bool($category) && strtolower($key) !== strtolower($category)) {
                continue;
            }

            $items = ($value["type"] === "bourse") ? Cache::$dynamic["bourse"] : $value["items"];

            foreach ($items as $_item) {
                list($name, $id, $meta, , $sell) = explode(":", $_item);

                if (intval($sell) !== 0 && $item->getId() === intval($id) && $item->getMeta() === intval($meta)) {
                    $itemName = $name;
                    $sellPrice = $sell;
                    break;
                }
            }
        }

        if ($sellPrice === 0) {
            return [false];
        } else {
            return [true, $itemName, $sellPrice];
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("opt", ["all"], true));
        $this->registerArgument(1, new OptionArgument("categorie", ["agriculture", "blocs", "bois", "minerais", "colorant", "autre"], true));
    }
}