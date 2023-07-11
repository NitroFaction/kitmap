<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player;

use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Pack as Api;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Pack extends BaseCommand {
	public function __construct(PluginBase $plugin) {
		parent::__construct(
			$plugin,
			"pack",
			"Ouvre le menu des packs"
		);

		$this->setPermissions([ DefaultPermissions::ROOT_USER ]);
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void {
		if ($sender instanceof Player) {
			if (Session::get($sender)->inCooldown("combat")) {
				$sender->sendMessage(Util::PREFIX . "Cette commande est interdite en combat");
				return;
			}

			Api::openPackUI($sender);
		}
	}

	protected function prepare() : void {
	}
}