<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\Main;
use Kitmap\task\repeat\KothTask;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;
use skymin\bossbar\BossBarAPI;

class Koth extends BaseCommand {
	public function __construct(PluginBase $plugin) {
		parent::__construct(
			$plugin,
			"koth",
			"Commence ou arrête un event koth !"
		);

		$this->setPermissions([ DefaultPermissions::ROOT_OPERATOR ]);
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void {
		switch ($args["opt"]) {
			case "start":
				if (is_numeric(KothTask::$currentKoth)) {
					$sender->sendMessage(Util::PREFIX . "Un event §eKOTH §fest déjà en cours... Vous pouvez l'arrêter avec la commande §e/koth end");
					return;
				}

				KothTask::$currentKoth = 180;
				Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Un event §eKOTH §fvient de commencer ! Vous pouvez vous y téléporter grace à la commande §e/event koth");
				break;
			case "end":
				KothTask::$currentKoth = null;
				KothTask::$currentPlayer = null;

				foreach (Main::getInstance()->getServer()->getOnlinePlayers() as $player) {
					BossBarAPI::getInstance()->hideBossBar($player, 1);
				}

				Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "L'event §eKOTH §fa été arrêté, pas de stuff :/");
				break;
		}
	}

	protected function prepare() : void {
		$this->registerArgument(0, new OptionArgument("opt", [ "start", "end" ]));
	}
}