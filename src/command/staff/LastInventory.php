<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TargetArgument;
use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\SimpleForm;
use Kitmap\handler\Cache;
use Kitmap\handler\discord\Discord;
use Kitmap\handler\discord\EmbedBuilder;
use Kitmap\handler\Rank;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\command\CommandSender;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
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

        $this->setPermissions([Rank::GROUP_STAFF]);
    }

    public static function saveOnlineInventory(Player $player, ?Player $damager, int $killstreak): void
    {
        self::saveInventory($player->getName(), $damager, $player->saveNBT(), $player->getXpManager()->getCurrentTotalXp(), $killstreak);
    }

    public static function saveInventory(string $playerName, ?Player $damager, CompoundTag $nbt, int $xp, int $killstreak): void
    {
        $file = Util::getFile("data/inventories/" . strtolower($playerName));
        $data = $file->getAll();

        $contents = Util::serializeCompoundTag($nbt);

        do {
            $id = rand(1, 9999);
        } while (isset($data["save"][$id]));

        $damagerName = match (true) {
            $damager instanceof Player => $damager->getName(),
            default => "Nature"
        };

        $data["save"][$id] = [
            "data" => $contents,
            "xp" => $xp,
            "date" => date("Y-m-d H:i"),
            "killstreak" => $killstreak,
            "killer" => $damagerName,
        ];

        $file->setAll($data);
        $file->save();
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $target = strtolower($args["joueur"]);

            if (in_array(Session::get($sender)->data["rank"], ["guide", "moderateur"])) {
                $sender->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de faire cela");
                return;
            }

            $form = new SimpleForm(function (Player $player, mixed $data) use ($target) {
                if (!is_string($data) || $data === "none") {
                    return;
                }

                $this->informationForm($player, $target, $data);
            });

            $file = Util::getFile("data/inventories/" . $target);
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
        $file = Util::getFile("data/inventories/" . $target);
        $information = $file->getAll()["save"][$data] ?? null;

        if (is_null($information)) {
            $player->sendMessage(Util::PREFIX . "Une erreur est survenue, l'inventaire choisi n'existe plus");
            return;
        }

        $message = "§6- §fXP: §6" . $information["xp"] . "\n§6- §fDate: §6" . $information["date"] . "\n§6- §fKillstreak: §6" . $information["killstreak"];

        $form = new SimpleForm(function (?Player $player, mixed $choice) use ($target, $data) {
            if ($choice === 0) {
                $this->sendInventory($player, $target, $data);
            }
        });

        if (!is_null($information["killer"])) {
            $killer = $information["killer"];
            $message .= "\n§6- §fTueur: " . $killer . "\n";

            if (isset(Cache::$bans[strtolower($killer)])) {
                $message .= "§6- §fLe tueur est actuellement banni";
            } else {
                $message .= "§6- §fLe tueur n'est pas banni";
            }
        } else {
            $message .= "§6- Le joueur est mort de façon naturel";
        }

        $form->setTitle("Inventaires");
        $form->setContent(Util::PREFIX . "Information sur la mort du joueur " . $target . "\n\n" . $message);
        $form->addButton("Passer à l'inventaire");
        $form->addButton("Annuler");
        $player->sendForm($form);
    }

    private function sendInventory(Player $player, string $target, string $data): void
    {
        $file = Util::getFile("data/inventories/" . $target);
        $inventory = $file->getAll()["save"][$data] ?? null;

        if (is_null($inventory)) {
            Util::removeCurrentWindow($player);
            $player->sendMessage(Util::PREFIX . "L'inventaire vient d'être rendu");
            return;
        }

        $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        $menu->setName($inventory["date"] . " | " . $target);

        $menu->setListener(InvMenu::readonly(function (DeterministicInvMenuTransaction $transaction) use ($player, $target, $file, $data): void {
            $array = $file->getAll();
            $inventory = $array["save"][$data] ?? null;

            if ($transaction->getItemClicked()->getCustomName() !== "§r§6Rendre l'inventaire") {
                return;
            } else if (is_null($inventory)) {
                Util::removeCurrentWindow($player);
                $player->sendMessage(Util::PREFIX . "L'inventaire vient d'être rendu");
                return;
            }

            $array["reclaim"][$data] = $inventory;
            unset($array["save"][$data]);

            $file->setAll($array);
            $file->save();

            Util::removeCurrentWindow($player);

            $player->sendMessage(Util::PREFIX . "Vous venez de rendre l'inventaire du joueur §6" . $target . " §fde sa mort datant du §6" . $inventory["date"]);
            Main::getInstance()->getLogger()->info("Le staff " . $player->getName() . " vient de rembourser l'inventaire d'une precedente mort du joueur " . $target);

            $embed = new EmbedBuilder();
            $embed->setDescription("**Remboursement**\n\n**Joueur**\n" . $target . "\n\n*Remboursement par le staff: " . $player->getName() . "*");
            $embed->setColor(5636095);
            Discord::send($embed, Cache::$config["sanction-webhook"]);
        }));

        $contents = $inventory["data"];
        $nbt = Util::deserializePlayerData($target, $contents);

        $count = 46;

        foreach (Util::readInventory($nbt) as $slot => $item) {
            $menu->getInventory()->setItem($slot, $item);
        }

        foreach (Util::readArmorInventory($nbt) as $item) {
            $menu->getInventory()->setItem($count, $item);
            $count++;
        }

        $menu->getInventory()->setItem(51, VanillaItems::PAPER()->setCustomName("§r§6Rendre l'inventaire"));
        $menu->send($player);
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetArgument("joueur"));
        $this->registerArgument(0, new RawStringArgument("joueur"));
    }
}