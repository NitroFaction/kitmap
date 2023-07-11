<?php

namespace Kitmap\command\faction\subcommands;

use CortexPE\Commando\args\RawStringArgument;
use Kitmap\command\faction\FactionCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\Faction;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Demote extends FactionCommand {
	public function __construct() {
		parent::__construct(
			Main::getInstance(),
			"demote",
			"Rétrograde un membre de sa faction"
		);

		$this->setPermissions([ DefaultPermissions::ROOT_USER ]);
	}

	public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args) : void {
		$sender_rank = Faction::getFactionRank($sender);

		if (!in_array($args["membre"], Faction::getFactionMembers($faction, false))) {
			$sender->sendMessage(Util::PREFIX . "Ce joueur n'est pas dans votre faction (verifiez les majuscules)");
			return;
		}

		$target_rank = Faction::getFactionRank($faction, $args["membre"]);
		$previous_rank = Faction::getPreviousRank($target_rank);

		$sender_position = Faction::getRankPosition($sender_rank);
		$target_position = Faction::getRankPosition($target_rank);

		if (!($target_position > $sender_position)) {
			$sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas rétrograder un joueur qui a votre rang ou si il a un meilleur rang que vous");
			return;
		}

		$rank_name = Cache::$config["faction_ranks"][$previous_rank];

		unset(Cache::$factions[$faction]["members"][$target_rank . "s"][array_search($args["membre"], Cache::$factions[$faction]["members"][$target_rank . "s"])]);
		Cache::$factions[$faction]["members"][$previous_rank . "s"][] = $args["membre"];

		Cache::$factions[$faction]["logs"][time()] = "§e" . $sender->getName() . " §fdemote §e" . $args["membre"] . "§f" . $rank_name;
		Faction::broadcastMessage($faction, "§e[§fF§e] §fLe joueur §e" . $args["membre"] . " §fvient d'être rétogradé §e" . $rank_name);
	}

	protected function prepare() : void {
		$this->registerArgument(0, new RawStringArgument("membre"));
	}
}