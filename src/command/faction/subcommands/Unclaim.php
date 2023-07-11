<?php

namespace Kitmap\command\faction\subcommands;

use Kitmap\command\faction\FactionCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\Faction;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Unclaim extends FactionCommand {
	public function __construct() {
		parent::__construct(
			Main::getInstance(),
			"unclaim",
			"Supprimer votre claim actuel"
		);

		$this->setPermissions([ DefaultPermissions::ROOT_USER ]);
	}

	public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args) : void {
		if (is_null(Cache::$factions[$faction]["claim"])) {
			$sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas supprimer votre claim si vous n'en avez pas");
			return;
		}

		$claim = Cache::$factions[$faction]["claim"];
		Cache::$factions[$faction]["claim"] = null;

		unset(Cache::$claims[$claim]);

		Cache::$factions[$faction]["logs"][time()] = "§e" . $sender->getName() . " §funclaim l'ancien claim";
		Faction::broadcastMessage($faction, "§e[§fF§r§e] §fVotre faction vient de supprimer votre claim actuel");
	}

	protected function prepare() : void {
	}
}