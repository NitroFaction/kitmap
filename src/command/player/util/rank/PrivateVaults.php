<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\rank;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\type\InvMenuTypeIds;
use NCore\handler\RankAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
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
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);
            $chest = $args["coffre"] ?? false;

            $rank = RankAPI::getEqualRank($sender->getName());
            $vaults = RankAPI::getRankValue($rank, "pv");

            if ($session->data["player"]["staff_mod"][0] || $sender->getGamemode() === GameMode::SPECTATOR()) {
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

            $playerVaults = $session->data["player"]["private_vaults"] ?? [];

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

        $items = $session->data["player"]["private_vaults"][$vault]["inventory"] ?? [];
        $name = $session->data["player"]["private_vaults"][$vault]["name"] ?? "Coffre #" . $vault;

        $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        $menu->setName($name);

        $menu->setListener(function (InvMenuTransaction $transaction) use ($player, $vault, $session): InvMenuTransactionResult {
            if ($player->getNetworkSession()->getPing() > 500) {
                return $transaction->discard();
            }

            $session->data["player"]["private_vaults"][$vault]["inventory"][$transaction->getAction()->getSlot()] = $transaction->getIn()->jsonSerialize();
            return $transaction->continue();
        });

        foreach ($items as $index => $item) {
            $menu->getInventory()->setItem($index, Item::jsonDeserialize($item));
        }

        $menu->send($player);
    }

    private function chooseForm(Player $player, string $vault): void
    {
        $session = Session::get($player);

        $playerVaults = $session->data["player"]["private_vaults"] ?? [];
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

            $session->data["player"]["private_vaults"][$vault]["name"] = $name;

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