<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player;

use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\ScoreFactory;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Scoreboard extends BaseCommand {
	public function __construct(PluginBase $plugin) {
		parent::__construct(
			$plugin,
			"scoreboard",
			"Active ou désactive le scoreboard"
		);
		$this->setPermissions([ DefaultPermissions::ROOT_USER ]);
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void {
		if ($sender instanceof Player) {
			$session = Session::get($sender);

			if ($session->data["scoreboard"]) {
				$session->data["scoreboard"] = false;
				unset(Cache::$scoreboardPlayers[$sender]);

				$sender->sendMessage(Util::PREFIX . "Vous ne verrez plus le scoreboard");
				ScoreFactory::removeScore($sender);
			} else {
				$session->data["scoreboard"] = true;
				$sender->sendMessage(Util::PREFIX . "Vous voyez desormais le scoreboard");

				Util::givePlayerPreferences($sender);
				ScoreFactory::updateScoreboard($sender);
			}
		}
	}

	protected function prepare() : void {
	}
}