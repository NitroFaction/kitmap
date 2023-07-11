<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;

class Save extends BaseCommand {
	public function __construct(PluginBase $plugin) {
		parent::__construct(
			$plugin,
			"save",
			"Sauvegarde les données des factions, hdv, etc.."
		);

		$this->setPermissions([ DefaultPermissions::ROOT_OPERATOR ]);
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void {
		Cache::getInstance()->saveAll();
		$sender->sendMessage(Util::PREFIX . "Vous venez de sauvegarder les données du serveur");
	}

	protected function prepare() : void {
	}
}