<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player;

use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\SimpleForm;
use Kitmap\handler\Rank;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Reclaim extends BaseCommand {
	public function __construct(PluginBase $plugin) {
		parent::__construct(
			$plugin,
			"reclaim",
			"Recupere ses récompenses journalières ou un remboursement"
		);

		$this->setPermissions([ DefaultPermissions::ROOT_USER ]);
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void {
		if ($sender instanceof Player) {
			$session = Session::get($sender);
			$file = Util::getFile("data/inventories/" . strtolower($sender->getName()));

			$inventorys = $file->getAll()["reclaim"] ?? [];

			if (count($inventorys) > 0) {
				$this->sendRefundForm($sender);
				return;
			} elseif ($session->inCooldown("reclaim")) {
				$format = Util::formatDurationFromSeconds($session->getCooldownData("reclaim")[0] - time());
				$sender->sendMessage(Util::PREFIX . "Vous ne pourrez avoir vos pack(s) journalier que dans: §e" . $format);
				return;
			}

			$rank = Rank::getEqualRank($sender->getName());
			$pack = Rank::getRankValue($rank, "pack");

			if ($pack === 0) {
				$sender->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de faire cela");
				return;
			}

			$session->addValue("pack", $pack);
			$session->setCooldown("reclaim", 60 * 60 * 24);

			$sender->sendMessage(Util::PREFIX . "Vous venez de recevoir §e" . $pack . " §fpack(s) grace à votre reclaim !");
			Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le joueur §e" . $sender->getName() . " §fvient de recevoir §e" . $pack . " §fpack(s) grace à son reclaim !");
		}
	}

	private function sendRefundForm(Player $player) : void {
		$file = Util::getFile("data/inventories/" . strtolower($player->getName()));
		$inventorys = $file->getAll()["reclaim"] ?? [];

		$form = new SimpleForm(function(Player $player, mixed $data) {
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

	private function informationForm(Player $player, string $inventory) : void {
		$file = Util::getFile("data/inventories/" . strtolower($player->getName()));

		$data = $file->getAll()["reclaim"][$inventory] ?? [];

		$nbt = Util::deserializePlayerData($player->getName(), $data["data"] ?? "");
		$items = $this->getItems($nbt);

		$form = new SimpleForm(function(Player $player, mixed $button) use ($file, $inventory) {
			if ($button === 0) {
				$data = $file->getAll()["reclaim"][$inventory] ?? [];
				$nbt = Util::deserializePlayerData($player->getName(), $data["data"] ?? "");

				foreach ($this->getItems($nbt) as $item) {
					Util::addItem($player, $item);
				}

				$session = Session::get($player);
				$session->addValue("death", 1, true);

				$player->sendMessage(Util::PREFIX . "Vous venez de récupérer votre inventaire que vous avez perdu le §e" . $data["date"]);
				$player->sendMessage(Util::PREFIX . "Une mort a été soustraite de votre compteur de mort");
				$player->sendMessage(Util::PREFIX . "Vous venez de récupérer votre xp");

				$player->getXpManager()->setCurrentTotalXp($data["xp"] + $player->getXpManager()->getCurrentTotalXp());

				if ($data["killstreak"] > $session->data["killstreak"]) {
					$session->data["killstreak"] = $data["killstreak"];
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

	private function getItems(CompoundTag $nbt) : array {
		$inventory = Util::readInventory($nbt);
		$armorInventory = Util::readArmorInventory($nbt);

		return array_merge($inventory, $armorInventory);
	}

	protected function prepare() : void {
	}
}