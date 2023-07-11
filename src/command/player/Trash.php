<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player;

use CortexPE\Commando\BaseCommand;
use Kitmap\Session;
use Kitmap\Util;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Trash extends BaseCommand {
	public function __construct(PluginBase $plugin) {
		parent::__construct(
			$plugin,
			"trash",
			"Ouvre un inventaire pour jeter ses items"
		);

		$this->setAliases([ "poubelle" ]);
		$this->setPermissions([ DefaultPermissions::ROOT_USER ]);
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void {
		if ($sender instanceof Player) {
			if (Session::get($sender)->inCooldown("combat")) {
				$sender->sendMessage(Util::PREFIX . "Cette commande est interdite en combat");
				return;
			}

			$inventory = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);
			$inventory->setName("Poubelle");
			$inventory->send($sender);
		}
	}

	protected function prepare() : void {
	}
}