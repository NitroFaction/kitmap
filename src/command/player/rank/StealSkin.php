<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player\rank;

use CortexPE\Commando\args\TargetArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Rank;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class StealSkin extends BaseCommand {
	public function __construct(PluginBase $plugin) {
		parent::__construct(
			$plugin,
			"stealskin",
			"Vole le skin d'un autre joueur"
		);

		$this->setPermissions([ DefaultPermissions::ROOT_USER ]);
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void {
		if ($sender instanceof Player) {
			/** @noinspection PhpDeprecationInspection */
			$player = Main::getInstance()->getServer()->getPlayerByPrefix($args["joueur"]);

			if (!Rank::hasRank($sender, "roi")) {
				$sender->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de faire cela");
				return;
			} elseif (!$player instanceof Player) {
				$sender->sendMessage(Util::PREFIX . "Le joueur n'éxiste pas ou n'est pas connecté sur le serveur");
				return;
			}

			$sender->setSkin($player->getSkin());
			$sender->sendSkin();

			$sender->sendMessage(Util::PREFIX . "Vous venez de voler le skin de §e" . $player->getName());
		}
	}

	protected function prepare() : void {
		$this->registerArgument(0, new TargetArgument("joueur"));
	}
}