<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\faction;

use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\SimpleForm;
use NCore\Base;
use NCore\handler\OtherAPI;
use NCore\handler\RankAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Reclaim extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "reclaim",
            "Recupere ses récompenses journalières ou un remboursement"
        );
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);
            $file = Util::getFile("inventories/" . strtolower($sender->getName()));

            $inventorys = $file->getAll()["reclaim"] ?? [];

            if (count($inventorys) > 0) {
                $this->sendRefundForm($sender);
                return;
            } else if ($session->inCooldown("reclaim")) {
                $seconds = $session->getCooldownData("reclaim")[0] - time();

                $_seconds = $seconds % 60;
                $minutes = floor(($seconds % 3600) / 60);
                $hours = floor(($seconds % 86400) / 3600);

                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez actuellement pas récuperer vos pack(s) journalier, merci d'attendre §e" . $hours . " §fheures, §e" . $minutes . " §fminutes et §e" . $_seconds . " §fsecondes !");
                return;
            }

            $rank = RankAPI::getEqualRank($sender->getName());
            $pack = RankAPI::getRankValue($rank, "pack");

            if ($pack === 0) {
                $sender->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de faire cela");
                return;
            }

            $session->addValue("pack", $pack);
            $session->setCooldown("reclaim", 60 * 60 * 24);

            $sender->sendMessage(Util::PREFIX . "Vous venez de recevoir §e" . $pack . " §fpack(s) grace à votre reclaim !");
            Base::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le joueur §e" . $sender->getName() . " §fvient de recevoir §e" . $pack . " §fpack(s) grace à son reclaim !");
        }
    }

    private function sendRefundForm(Player $player): void
    {
        $file = Util::getFile("inventories/" . strtolower($player->getName()));
        $inventorys = $file->getAll()["reclaim"] ?? [];

        $form = new SimpleForm(function (Player $player, mixed $data) {
            if (!is_string($data)) {
                return;
            }

            $this->informationForm($player, $data);
        });

        foreach ($inventorys as $key => $value) {
            $form->addButton("Mort par §e" . $value["killer"], -1, "", $key);
        }

        $form->setTitle("Remboursement");
        $form->setContent(Util::PREFIX . "Cliquez sur le bouton de choix");
        $player->sendForm($form);
    }

    private function informationForm(Player $player, string $inventory): void
    {
        $file = Util::getFile("inventories/" . strtolower($player->getName()));

        $data = $file->getAll()["reclaim"][$inventory] ?? [];
        $items = $this->sortItems($data);

        $form = new SimpleForm(function (Player $player, mixed $button) use ($file, $inventory) {
            $data = $file->getAll()["reclaim"][$inventory] ?? [];
            $items = $this->sortItems($data);

            if ($button === 0) {
                foreach ($items as $item) {
                    OtherAPI::addItem($player, $item);
                }

                $session = Session::get($player);
                $session->addValue("death", 1, true);

                $player->sendMessage(Util::PREFIX . "Vous venez de récupérer votre inventaire que vous avez perdu le §e" . $data["date"]);
                $player->sendMessage(Util::PREFIX . "Une mort a été soustraite de votre compteur de mort");
                $player->sendMessage(Util::PREFIX . "Vous venez de récupérer votre xp");

                $player->getXpManager()->setCurrentTotalXp($data["xp"] + $player->getXpManager()->getCurrentTotalXp());

                if ($data["killstreak"] > $session->data["player"]["killstreak"]) {
                    $session->data["player"]["killstreak"] = $data["killstreak"];
                    $player->sendMessage(Util::PREFIX . "Votre killstreak a été restoré");
                }

                $data = $file->getAll();
                unset($data["reclaim"][$inventory]);

                $file->setAll($data);
                $file->save();
            }
        });

        $form->setTitle("Remboursement");
        $form->setContent("§fL'inventaire contient §e" . count($items) . " §fitems\nVerifiez que votre inventaire a assez de place pour récupérer les items");
        $form->addButton("Récupérer l'inventaire");
        $form->addButton("Récupérer plus tard");
        $player->sendForm($form);
    }

    private function sortItems(array $inventory): array
    {
        $items = [];

        foreach (array_merge($inventory["armor"], $inventory["items"]) as $item) {
            $item = Item::jsonDeserialize($item);

            if ($item->getId() !== ItemIds::SPLASH_POTION) {
                $items[] = $item;
            }
        }
        return $items;
    }

    protected function prepare(): void
    {
    }
}