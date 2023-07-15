<?php

namespace Kitmap\handler;

use Kitmap\Main;
use Kitmap\Session;
use Kitmap\task\repeat\DominationTask;
use Kitmap\Util;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\player\Player;
use function mb_strtolower;

class ScoreFactory
{
    private const OBJECTIVE_NAME = "objective";
    private const CRITERIA_NAME = "dummy";

    private static array $scoreboards = [];

    public static function updateScoreboard(Player $player): void
    {
        $session = Session::get($player);

        if (!$session->data["scoreboard"]) {
            return;
        }

        if (self::hasScore($player)) {
            if (DominationTask::$currentDomination) {
                self::setScore($player, "§eDomination (§7" . date("H:i") . "§e)");
                $lines = DominationTask::getScoreboardLines();
            } else {
                self::setScore($player, "§eNitro (§7" . date("H:i") . "§e)");

                $rank = ($player->getName() === $player->getDisplayName()) ? ucfirst(strtolower($session->data["rank"])) : "Joueur";
                $faction = Faction::hasFaction($player) ? Faction::getFactionUpperName($session->data["faction"]) : "Aucune";

                $money = Util::formatNumberWithSuffix($session->data["money"]);
                $voteparty = Cache::$data["voteparty"] ?? 0;

                $lines = [
                    "§f ",
                    "§l§e" . $player->getDisplayName(),
                    "§fGrade: §e" . $rank,
                    "§fFaction: §e" . $faction,
                    "§fPieces: §e" . $money,
                    "§r ",
                    "§l§eServeur",
                    "§fConnectés: §e" . count(Main::getInstance()->getServer()->getOnlinePlayers()),
                    "§fVoteParty: §e" . $voteparty . "§f/§e100",
                    "§7 ",
                    "     §7nitrofaction.fr    "
                ];
            }

            foreach ($lines as $key => $value) {
                self::setScoreLine($player, $key + 1, $value);
            }
        } else {
            self::setScore($player, "§eNitro (§7" . date("H:i") . "§e)");
            self::updateScoreboard($player);
        }
    }

    public static function hasScore(Player $player): bool
    {
        return isset(self::$scoreboards[mb_strtolower($player->getXuid())]);
    }

    public static function setScore(Player $player, string $displayName, int $slotOrder = SetDisplayObjectivePacket::SORT_ORDER_ASCENDING, string $displaySlot = SetDisplayObjectivePacket::DISPLAY_SLOT_SIDEBAR, string $objectiveName = self::OBJECTIVE_NAME, string $criteriaName = self::CRITERIA_NAME): void
    {
        if (isset(self::$scoreboards[mb_strtolower($player->getXuid())])) {
            self::removeScore($player);
        }

        $pk = new SetDisplayObjectivePacket();
        $pk->displaySlot = $displaySlot;

        $pk->objectiveName = $objectiveName;
        $pk->displayName = $displayName;

        $pk->criteriaName = $criteriaName;
        $pk->sortOrder = $slotOrder;

        $player->getNetworkSession()->sendDataPacket($pk);
        self::$scoreboards[mb_strtolower($player->getXuid())] = $objectiveName;
    }

    public static function removeScore(Player $player): void
    {
        $objectiveName = self::$scoreboards[mb_strtolower($player->getXuid())] ?? self::OBJECTIVE_NAME;
        $pk = new RemoveObjectivePacket();
        $pk->objectiveName = $objectiveName;
        $player->getNetworkSession()->sendDataPacket($pk);
        unset(self::$scoreboards[mb_strtolower($player->getXuid())]);
    }

    public static function setScoreLine(Player $player, int $line, string $message, int $type = ScorePacketEntry::TYPE_FAKE_PLAYER): void
    {
        $entry = new ScorePacketEntry();

        $entry->objectiveName = self::$scoreboards[mb_strtolower($player->getXuid())] ?? self::OBJECTIVE_NAME;
        $entry->type = $type;
        $entry->customName = $message;
        $entry->score = $line;
        $entry->scoreboardId = $line;

        $pk = new SetScorePacket();

        $pk->type = $pk::TYPE_CHANGE;
        $pk->entries[] = $entry;

        $player->getNetworkSession()->sendDataPacket($pk);
    }
}