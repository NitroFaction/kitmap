<?php

namespace Kitmap\command\faction\subcommands;

use Kitmap\command\faction\FactionCommand;
use Kitmap\handler\Cache;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Delhome extends FactionCommand {
	public function __construct() {
		parent::__construct(
			Main::getInstance(),
			"delhome",
			"Supprime le point de téléportation commun à une faction"
		);

		$this->setPermissions([ DefaultPermissions::ROOT_USER ]);
	}

	public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args) : void {
		Cache::$factions[$faction]["home"] = "0:0:0";
		Cache::$factions[$faction]["logs"][time()] = "§e" . $sender->getName() . " §fsupprime le f home";

		$sender->sendMessage(Util::PREFIX . "Vous venez de supprimer le point de téléportation de votre home");
	}

	protected function prepare() : void {
	}
}