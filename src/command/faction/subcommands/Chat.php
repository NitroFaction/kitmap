<?php

namespace Kitmap\command\faction\subcommands;

use Kitmap\command\faction\FactionCommand;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Chat extends FactionCommand {
	public function __construct() {
		parent::__construct(
			Main::getInstance(),
			"chat",
			"Active ou desactive le chat de faction"
		);

		$this->setPermissions([ DefaultPermissions::ROOT_USER ]);
	}

	public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args) : void {
		if ($session->data["faction_chat"]) {
			$session->data["faction_chat"] = false;
			$sender->sendMessage(Util::PREFIX . "Vous venez de de desactiver le chat de faction");
		} else {
			$session->data["faction_chat"] = true;
			$sender->sendMessage(Util::PREFIX . "Vous venez d'activer le chat de faction");
		}
	}

	protected function prepare() : void {
	}
}