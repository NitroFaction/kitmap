<?php /** @noinspection PhpUnused */

namespace NCore\command\staff\tool;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TargetArgument;
use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\SimpleForm;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use muqsit\invmenu\type\InvMenuTypeIds;
use NCore\Base;
use NCore\handler\Cache;
use NCore\handler\discord\Discord;
use NCore\handler\discord\EmbedBuilder;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class LastInventory extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "lastinventory",
            "Récupére les derniers inventaires d'un joueur avant sa mort"
        );

        $this->setPermission("staff.group");
    }

    public static function saveOnlineInventory(Player $player, ?Player $damager, int $killstreak): void
    {
        self::saveInventory($player->getName(), $damager, $player->getInventory(), $player->getArmorInventory(), $player->getXpManager()->getCurrentTotalXp(), $killstreak);
    }

    public static function saveInventory(string $playerName, ?Player $damager, PlayerInventory $inventory, ArmorInventory $armorInventory, int $xp, int $killstreak): void
    {
        $file = Util::getFile("inventories/" . strtolower($playerName));
        $data = $file->getAll();

        $armor = $items = [];

        foreach ($inventory->getContents() as $slot => $item) $items[$slot] = $item->jsonSerialize();
        foreach ($armorInventory->getContents() as $slot => $item) $armor[$slot] = $item->jsonSerialize();

        do {
            $id = rand(1, 9999);
        } while (isset($data["save"][$id]));

        $damagerName = match (true) {
            $damager instanceof Player => $damager->getName(),
            default => "Nature"
        };

        $data["save"][$id] = [
            "armor" => $armor,
            "items" => $items,
            "xp" => $xp,
            "date" => strftime("%A %d %B %H:%M"),
            "killstreak" => $killstreak,
            "killer" => $damagerName
        ];

        $file->setAll($data);
        $file->save();
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $target = strtolower($args["joueur"]);

            if (in_array(Session::get($sender)->data["player"]["rank"], ["guide", "moderateur"])) {
                $sender->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de faire cela");
                return;
            }

            $form = new SimpleForm(function (Player $player, mixed $data) use ($target) {
                if (!is_string($data) || $data === "none") {
                    return;
                }

                $this->informationForm($player, $target, $data);
            });

            $file = Util::getFile("inventories/" . $target);
            $arr = $file->getAll()["save"] ?? [];

            if (count($arr) !== 0) {
                foreach ($arr as $key => $value) {
                    $form->addButton($value["date"], -1, "", $key);
                }
            } else {
                $form->addButton("Aucun inventaire", -1, "", "none");
            }

            $form->setTitle("Inventaires");
            $form->setContent(Util::PREFIX . "Cliquez sur le boutton de votre choix");
            $sender->sendForm($form);
        }
    }

    private function informationForm(Player $player, string $target, string $data): void
    {
        $file = Util::getFile("inventories/" . $target);
        $information = $file->getAll()["save"][$data] ?? null;

        if (is_null($information)) {
            $player->sendMessage(Util::PREFIX . "Une erreur est survenue, l'inventaire choisi n'existe plus");
            return;
        }

        $message = "§e- §fXP: §e" . $information["xp"] . "\n§e- §fDate: §e" . $information["date"] . "\n§e- §fKillstreak: §e" . $information["killstreak"];

        $form = new SimpleForm(function (?Player $player, mixed $choice) use ($target, $data) {
            if ($choice === 0) {
                $this->sendInventory($player, $target, $data);
            }
        });

        if (!is_null($information["killer"])) {
            $killer = $information["killer"];
            $message .= "\n§e- §fTueur: " . $killer . "\n";

            if (isset(Cache::$bans[strtolower($killer)])) {
                $message .= "§e- §fLe tueur est actuellement banni";
            } else {
                $message .= "§e- §fLe tueur n'est pas banni";
            }
        } else {
            $message .= "§e- Le joueur est mort de façon naturel";
        }

        $form->setTitle("Inventaires");
        $form->setContent(Util::PREFIX . "Information sur la mort du joueur " . $target . "\n\n" . $message);
        $form->addButton("Passer à l'inventaire");
        $form->addButton("Annuler");
        $player->sendForm($form);
    }

    private function sendInventory(Player $player, string $target, string $data): void
    {
        $file = Util::getFile("inventories/" . $target);
        $inventory = $file->getAll()["save"][$data] ?? null;

        if (is_null($inventory)) {
            $player->removeCurrentWindow();
            $player->sendMessage(Util::PREFIX . "L'inventaire vient d'être rendu");
            return;
        }

        $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        $menu->setName($inventory["date"] . " | " . $target);

        $menu->setListener(InvMenu::readonly(function (DeterministicInvMenuTransaction $transaction) use ($player, $target, $file, $data): void {
            $array = $file->getAll();
            $inventory = $array["save"][$data] ?? null;

            if ($transaction->getItemClicked()->getCustomName() !== "§r§eRendre l'inventaire") {
                return;
            } else if (is_null($inventory)) {
                $player->removeCurrentWindow();
                $player->sendMessage(Util::PREFIX . "L'inventaire vient d'être rendu");
                return;
            }

            $array["reclaim"][$data] = $inventory;
            unset($array["save"][$data]);

            $file->setAll($array);
            $file->save();

            $player->removeCurrentWindow();

            $player->sendMessage(Util::PREFIX . "Vous venez de rendre l'inventaire du joueur §e" . $target . " §fde sa mort datant du §e" . $inventory["date"]);
            Base::getInstance()->getLogger()->info("Le staff " . $player->getName() . " vient de rembourser l'inventaire d'une precedente mort du joueur " . $target);

            $embed = new EmbedBuilder();
            $embed->setDescription("**Remboursement**\n\n**Joueur**\n" . $target . "\n\n*Remboursement par le staff: " . $player->getName() . "*");
            $embed->setColor(5636095);
            Discord::send($embed, Cache::$config["sanction_webhook"]);
        }));

        foreach ($inventory["items"] as $slot => $item) {
            $menu->getInventory()->setItem($slot, Item::jsonDeserialize($item));
        }

        $count = 46;

        foreach ($inventory["armor"] as $item) {
            $menu->getInventory()->setItem($count, Item::jsonDeserialize($item));
            $count++;
        }

        $menu->getInventory()->setItem(51, ItemFactory::getInstance()->get(ItemIds::PAPER)->setCustomName("§r§eRendre l'inventaire"));
        $menu->send($player);
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetArgument("joueur"));
        $this->registerArgument(0, new RawStringArgument("joueur"));
    }
}