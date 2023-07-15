<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player\rank;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use Kitmap\command\util\market\Market;
use Kitmap\handler\Rank;
use Kitmap\Session;
use Kitmap\Util;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\command\CommandSender;
use pocketmine\inventory\Inventory;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class PrivateVaults extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "privatevaults",
            "Ouvre un coffre fort personnel"
        );

        $this->setAliases(["pv"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);
            $chest = $args["coffre"] ?? false;

            $rank = Rank::getEqualRank($sender->getName());
            $vaults = Rank::getRankValue($rank, "pv");

            if ($session->data["staff_mod"][0] || $sender->getGamemode() === GameMode::SPECTATOR()) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas accèder à votre enderchest en étant en staff mod");
                return;
            } else if ($session->inCooldown("combat")) {
                $sender->sendMessage(Util::PREFIX . "Cette commande est interdite en combat");
                return;
            } else if ($chest !== false) {
                $chest = intval($chest);

                if ($chest > $vaults || 1 > $chest) {
                    $sender->sendMessage(Util::PREFIX . "Vous n'avez pas accès à ce coffre");
                    return;
                }

                $this->openPrivateVault($sender, strval($chest));
                return;
            }

            $form = new SimpleForm(function (Player $player, mixed $data) use ($vaults) {
                if (!is_string($data)) {
                    return;
                } else if ($data > $vaults || 1 > $data) {
                    $player->sendMessage(Util::PREFIX . "Vous n'avez pas accès à ce coffre");
                    return;
                }

                $this->chooseForm($player, $data);
            });

            $playerVaults = $session->data["private_vaults"] ?? [];

            for ($i = 1; $i <= $vaults; $i++) {
                $name = $playerVaults[$i]["name"] ?? "Coffre #" . $i;
                $form->addButton($name, -1, "", strval($i));
            }

            $form->setTitle("Coffres Privés");
            $form->setContent(Util::PREFIX . "Cliquez sur le coffre de votre choix");
            $sender->sendForm($form);
        }
    }

    private function openPrivateVault(Player $player, string $vault): void
    {
        $session = Session::get($player);

        $items = $session->data["private_vaults"][$vault]["inventory"] ?? [];
        $name = $session->data["private_vaults"][$vault]["name"] ?? "Coffre #" . $vault;

        $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        $menu->setName($name);

        $menu->setListener(function (InvMenuTransaction $transaction) use ($player, $vault, $session): InvMenuTransactionResult {
            $item = $transaction->getIn();

            if ($item->isNull()) {
                unset($session->data["private_vaults"][$vault]["inventory"][$transaction->getAction()->getSlot()]);
            } else {
                $session->data["private_vaults"][$vault]["inventory"][$transaction->getAction()->getSlot()] = Market::serialize($item);
            }

            return $transaction->continue();
        });

        $menu->setInventoryCloseListener(function (Player $viewer, Inventory $inventory) use ($session): void {

        });

        foreach ($items as $index => $item) {
            $menu->getInventory()->setItem($index, Market::readItem(Market::deserialize($item)));
        }

        $menu->send($player);
    }

    private function chooseForm(Player $player, string $vault): void
    {
        $session = Session::get($player);

        $playerVaults = $session->data["private_vaults"] ?? [];
        $name = $playerVaults[$vault]["name"] ?? "Coffre #" . $vault;

        $form = new SimpleForm(function (Player $player, mixed $data) use ($vault) {
            if (!is_int($data)) {
                return;
            }

            switch ($data) {
                case 0:
                    $this->renameForm($player, $vault);
                    break;
                case 1:
                    $this->openPrivateVault($player, $vault);
                    break;
                case 2:
                    $player->chat("/" . $this->getName());
                    break;
            }
        });
        $form->setTitle($name);
        $form->addButton("Renommer le coffre");
        $form->addButton("Ouvrir le coffre");
        $form->addButton("Retour aux coffres");
        $player->sendForm($form);
    }

    private function renameForm(Player $player, int $vault): void
    {
        $form = new CustomForm(function (Player $player, mixed $data) use ($vault) {
            if (!is_array($data)) {
                return;
            }

            $session = Session::get($player);
            $name = $data[0];

            $session->data["private_vaults"][$vault]["name"] = $name;

            $player->sendMessage(Util::PREFIX . "Vous venez de renommer votre coffre privé §e" . $name . " §f(" . $vault . ")");
        });
        $form->setTitle("Coffres Privés");
        $form->addInput(Util::PREFIX . "Choissisez le nouveau nom du coffre:");
        $player->sendForm($form);
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("coffre", range(1, 10), true));
    }
}