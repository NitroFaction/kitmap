<?php

namespace Kitmap\handler;

use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\player\Player;

class Rank {
	public const GROUP_STAFF = "nitro.staff";

	public function __construct() {
		$permManager = PermissionManager::getInstance();
		$opRoot = $permManager->getPermission(DefaultPermissions::ROOT_OPERATOR);

		$permManager->addPermission(new Permission(self::GROUP_STAFF));
		$opRoot->addChild(self::GROUP_STAFF, true);
	}

	public static function existRank(string $rank) : bool {
		return isset(Cache::$config["ranks"][$rank]);
	}

	public static function getEqualRank(string $name) : string {
		$rank = self::getRank($name);

		if (self::isStaff($rank)) {
			return "roi";
		} else {
			if ($rank === "createur") {
				return "elite";
			}
			return $rank;
		}
	}

	public static function getRank(string $name) : ?string {
		$name = strtolower($name);

		/** @noinspection PhpDeprecationInspection */
		$player = Main::getInstance()->getServer()->getPlayerByPrefix($name);

		if ($player instanceof Player) {
			$session = Session::get($player);
			$rank = $session->data["rank"];
		} else {
			$file = Util::getFile("data/players/" . $name);
			$rank = $file->get("rank", "joueur");
		}
		return $rank;
	}

	public static function isStaff(?string $rank) : bool {
		return in_array($rank, [ "guide", "moderateur", "sm", "administrateur", "fondateur" ]);
	}

	public static function hasRank(Player $player, string $rank) : bool {
		return self::hasRankOffline(self::getRank($player->getName()), $rank);
	}

	public static function hasRankOffline(string $rank, string $needle) : bool {
		$ranks = array_keys(Cache::$config["ranks"]);
		return array_search($rank, $ranks) >= array_search($needle, $ranks);
	}

	public static function setRank(string $name, string $rank) : void {
		$name = strtolower($name);

		/** @noinspection PhpDeprecationInspection */
		$player = Main::getInstance()->getServer()->getPlayerByPrefix($name);

		if ($player instanceof Player) {
			$session = Session::get($player);
			$session->data["rank"] = $rank;

			self::updateNameTag($player);
			self::addPermissions($player);

			self::saveRank($player->getXuid(), $rank);
		} else {
			$file = Util::getFile("data/players/" . $name);

			if ($file->getAll() !== []) {
				$file->set("rank", $rank);
				$file->save();

				self::saveRank($file->get("xuid"), $rank);
			}
		}
	}

	public static function updateNameTag(Player $player) : void {
		$name = $player->getName();
		$rank = ($name === $player->getDisplayName()) ? self::getRank($name) : "joueur";

		$prefix = self::getRankValue($rank, "gamertag");
		$replace = self::setReplace($prefix, $player);

		$player->setNameTag($replace);
		$player->setNameTagAlwaysVisible();
	}

	public static function getRankValue(string $rank, string $value) : mixed {
		return Cache::$config["ranks"][$rank][$value];
	}

	public static function setReplace(string $replace, Player $player, string $msg = "") : string {
		$session = Session::get($player);
		Faction::hasFaction($player);

		$faction = $session->data["faction"];
		$faction = (is_null($faction)) ? "..." : Cache::$factions[$faction]["upper_name"];

		return str_replace(
			[ "{name}", "{fac}", "{msg}" ],
			[ $player->getDisplayName(), $faction, $msg ],
			$replace
		);
	}

	public static function addPermissions(Player $player) : void {
		$session = Session::get($player);

		if (Rank::isStaff($session->data["rank"]) || $player->hasPermission("pocketmine.group.operator")) {
			$player->addAttachment(Main::getInstance(), self::GROUP_STAFF, true);
			$player->addAttachment(Main::getInstance(), "pocketmine.command.teleport", true);

			if (!in_array($session->data["rank"], [ "guide", "moderateur" ])) {
				$player->addAttachment(Main::getInstance(), "pocketmine.command.gamemode", true);
			}
		}
	}

	public static function saveRank(string $value, string $key) : void {
		$file = Util::getFile("ownings");
		$data = $file->get($value, []);

		$data["rank"] = $key;

		$file->set($value, $data);
		$file->save();
	}
}