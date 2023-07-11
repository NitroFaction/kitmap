<?php /** @noinspection PhpUnused */

namespace Kitmap\command\util;

use CortexPE\Commando\args\TargetArgument;
use CortexPE\Commando\args\TextArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\world\sound\ClickSound;

class Mp extends BaseCommand {
	public function __construct(PluginBase $plugin) {
		parent::__construct(
			$plugin,
			"mp",
			"Envoie un message à un ou plusieurs joueurs"
		);

		$this->setAliases([ "msg", "tell" ]);
		$this->setPermissions([ DefaultPermissions::ROOT_USER ]);
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void {
		if ($sender instanceof Player) {
			$session = Session::get($sender);

			if ($session->inCooldown("mute")) {
				$sender->sendMessage(Util::PREFIX . "Vous êtes mute, temps restant: §e" . Util::formatDurationFromSeconds($session->getCooldownData("mute")[0] - time()));
				return;
			}

			/** @noinspection PhpDeprecationInspection */
			$player = Main::getInstance()->getServer()->getPlayerByPrefix(array_shift($args));

			if ($player instanceof Player) {
				Main::getInstance()->getLogger()->info("[MP] [" . $sender->getName() . " » " . $player->getName() . "] " . implode(" ", $args));

				$session->data["reply"] = $player->getName();
				Session::get($player)->data["reply"] = $sender->getName();

				foreach ([ $player, $sender ] as $players) {
					$players->sendMessage("§e[§fMP§e] §e[§f" . $sender->getName() . " " . Util::PREFIX . $player->getName() . "§e] §f" . implode(" ", $args));
					$players->broadcastSound(new ClickSound());
				}
			} else {
				$sender->sendMessage(Util::PREFIX . "Le joueur indiqué n'est pas connecté sur le serveur");
			}
		}
	}

	protected function prepare() : void {
		$this->registerArgument(0, new TargetArgument("joueur"));
		$this->registerArgument(1, new TextArgument("message"));
	}
}