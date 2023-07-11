<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff;

use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Rank;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\block\utils\DyeColor;
use pocketmine\command\CommandSender;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Vanish extends BaseCommand {
	public static array $vanish = [];

	public function __construct(PluginBase $plugin) {
		parent::__construct(
			$plugin,
			"vanish",
			"Disparaît aux yeux des autres joueurs"
		);

		$this->setPermissions([ Rank::GROUP_STAFF ]);
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void {
		if ($sender instanceof Player) {
			$item = $sender->getInventory()->getItemInHand();

			if (in_array($sender->getName(), Vanish::$vanish)) {
				foreach (Main::getInstance()->getServer()->getOnlinePlayers() as $player) {
					$player->showPlayer($sender);
				}

				unset(Vanish::$vanish[array_search($sender->getName(), Vanish::$vanish)]);
				$sender->sendMessage(Util::PREFIX . "Vous êtes désormais visible aux yeux des autres joueurs");

				if ($item->getCustomName() === "§r" . Util::PREFIX . "Vanish §e§l«") {
					$sender->getInventory()->setItemInHand(VanillaItems::DYE()->setColor(DyeColor::LIGHT_GRAY())->setCustomName("§r" . Util::PREFIX . "Vanish §e§l«"));
				}
			} else {
				foreach (Main::getInstance()->getServer()->getOnlinePlayers() as $player) {
					if ($player->hasPermission("staff.group") || $player->getName() === $sender->getName()) {
						continue;
					}

					$player->hidePlayer($sender);
				}

				Vanish::$vanish[] = $sender->getName();
				$sender->sendMessage(Util::PREFIX . "Vous êtes désormais invisible aux yeux des autres joueurs");

				if ($item->getCustomName() === "§r" . Util::PREFIX . "Vanish §e§l«") {
					$sender->getInventory()->setItemInHand(VanillaItems::DYE()->setColor(DyeColor::GREEN())->setCustomName("§r" . Util::PREFIX . "Vanish §e§l«"));
				}
			}
		}
	}

	protected function prepare() : void {
	}
}