<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\args\TargetArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\command\player\Kit;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\item\Armor;
use pocketmine\item\VanillaItems;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class GiveKit extends BaseCommand {
	public function __construct(PluginBase $plugin) {
		parent::__construct(
			$plugin,
			"givekit",
			"Donne un kit à un joueur"
		);

		$this->setPermissions([ DefaultPermissions::ROOT_OPERATOR ]);
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void {
		if ($args["joueur"] === "@a") {
			Util::allSelectorExecute($sender, $this->getName(), $args);
			return;
		}

		/** @noinspection PhpDeprecationInspection */
		$target = Main::getInstance()->getServer()->getPlayerByPrefix($args["joueur"]);
		$items = Kit::getKits()[$args["kit"]]["items"];

		if (!$target instanceof Player) {
			$sender->sendMessage(Util::PREFIX . "Le joueur indiqué n'est pas connecté sur le serveur");
			return;
		}

		foreach ($items as $item) {
			if ($item instanceof Armor) {
				if ($target->getArmorInventory()->getItem($item->getArmorSlot())->equals(VanillaItems::AIR())) {
					$target->getArmorInventory()->setItem($item->getArmorSlot(), $item);
					continue;
				}
			}

			if ($target->getInventory()->canAddItem($item)) {
				Util::addItem($target, $item);
			} else {
				$target->getWorld()->dropItem($target->getPosition()->asVector3(), $item);
			}
		}

		$sender->sendMessage(Util::PREFIX . "Vous venez de donner un kit §e" . $args["kit"] . " §fau joueur §e" . $target->getName());
		$target->sendMessage(Util::PREFIX . "Vous venez de recevoir le kit §e" . $args["kit"] . " §fde la part de §e" . $sender->getName());
	}

	protected function prepare() : void {
		$this->registerArgument(0, new TargetArgument("joueur"));
		$this->registerArgument(1, new OptionArgument("kit", array_keys(Kit::getKits())));
	}
}