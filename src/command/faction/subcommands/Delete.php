<?php

namespace Kitmap\command\faction\subcommands;

use Kitmap\command\faction\FactionCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\Faction;
use Kitmap\handler\Rank;
use Kitmap\Main;
use Kitmap\Session;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Delete extends FactionCommand {
	public function __construct() {
		parent::__construct(
			Main::getInstance(),
			"delete",
			"Supprimer sa faction"
		);

		$this->setPermissions([ DefaultPermissions::ROOT_USER ]);
		$this->setAliases([ "del", "disband" ]);
	}

	public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args) : void {
		Faction::broadcastMessage($faction, "§e[§fF§r§e] §fLa faction dont vous êtiez n'existe désormais plus");

		foreach (Faction::getFactionMembers($faction, true) as $player) {
			$session->data["faction"] = null;
			$session->data["faction_chat"] = false;

			Rank::updateNameTag($player);
		}

		if (!is_null(Cache::$factions[$faction]["claim"])) {
			$claim = Cache::$factions[$faction]["claim"];
			unset(Cache::$claims[$claim]);
		}

		unset(Cache::$factions[$faction]);
	}

	protected function prepare() : void {
	}
}