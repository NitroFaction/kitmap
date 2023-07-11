<?php

namespace Kitmap\command\faction\subcommands;

use CortexPE\Commando\args\RawStringArgument;
use Kitmap\command\faction\FactionCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\Faction;
use Kitmap\handler\Rank;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Kick extends FactionCommand {
	public function __construct() {
		parent::__construct(
			Main::getInstance(),
			"kick",
			"Expulser un joueur de sa faction"
		);

		$this->setPermissions([ DefaultPermissions::ROOT_USER ]);
	}

	public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args) : void {
		$member = $args["membre"];

		if (!in_array($member, Faction::getFactionMembers($faction, false))) {
			$sender->sendMessage(Util::PREFIX . "Ce joueur n'est pas dans votre faction (verifiez les majuscules)");
			return;
		}

		$sender_rank = Faction::getFactionRank($sender);
		$target_rank = Faction::getFactionRank($faction, $member);

		$sender_position = Faction::getRankPosition($sender_rank);
		$target_position = Faction::getRankPosition($target_rank);

		if (!($target_position > $sender_position)) {
			$sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas expulser un joueur qui a votre rang ou si il a un meilleur rang que vous");
			return;
		}

		/** @noinspection PhpDeprecationInspection */
		$target = Main::getInstance()->getServer()->getPlayerByPrefix($member);

		if ($target instanceof Player) {
			$targetSession = Session::get($target);

			$targetSession->data["faction"] = null;
			$targetSession->data["faction_chat"] = false;

			Rank::updateNameTag($target);
		}

		Cache::$factions[$faction]["logs"][time()] = "§e" . $sender->getName() . " §fkick §e" . $args["membre"];

		unset(Cache::$factions[$faction]["members"][$target_rank . "s"][array_search($args["membre"], Cache::$factions[$faction]["members"][$target_rank . "s"])]);
		Faction::broadcastMessage($faction, "§e[§fF§e] §fLe joueur §e" . $args["membre"] . " §fvient d'être expulsé de votre faction");
	}

	protected function prepare() : void {
		$this->registerArgument(0, new RawStringArgument("membre"));
	}
}