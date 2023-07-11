<?php /** @noinspection PhpUnused */

namespace Kitmap\command\util;

use CortexPE\Commando\BaseCommand;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\task\TeleportationTask;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Spawn extends BaseCommand {
	public function __construct(PluginBase $plugin) {
		parent::__construct(
			$plugin,
			"spawn",
			"Se téléporte au spawn"
		);

		$this->setPermissions([ DefaultPermissions::ROOT_USER ]);
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void {
		if ($sender instanceof Player) {
			$session = Session::get($sender);

			if ($session->inCooldown("combat")) {
				$sender->sendMessage(Util::PREFIX . "Cette commande est interdite en combat");
				return;
			} elseif ($session->inCooldown("teleportation")) {
				$sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas executer cette commande en teleportation");
				return;
			}

			Main::getInstance()->getScheduler()->scheduleRepeatingTask(new TeleportationTask($sender, Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation()), 20);
		}
	}

	protected function prepare() : void {
	}
}