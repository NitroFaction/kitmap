<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player;

use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\SimpleForm;
use Kitmap\Util;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class PreviewKit extends BaseCommand {
	public function __construct(PluginBase $plugin) {
		parent::__construct(
			$plugin,
			"previewkit",
			"Ouvre le menu pour prévisualiser les kits"
		);

		$this->setPermissions([ DefaultPermissions::ROOT_USER ]);
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void {
		if ($sender instanceof Player) {
			$form = new SimpleForm(function(Player $player, mixed $data) {
				if (!is_string($data)) {
					return;
				}

				$menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);

				$menu->setName("Kit " . ucfirst(strtolower($data)));
				$menu->setListener(InvMenu::readonly());

				foreach (Kit::getKits()[$data]["items"] as $item) {
					$menu->getInventory()->addItem($item);
				}

				$menu->send($player);
			});

			$form->setTitle("Prévisualisation Kit");
			$form->setContent(Util::PREFIX . "Quel kit voulez-vous prévisualiser");

			foreach (Kit::getKits() as $key => $value) {
				$name = ucfirst(strtolower($key));
				$form->addButton($name, -1, "", $key);
			}

			$sender->sendForm($form);
		}
	}

	protected function prepare() : void {
	}
}