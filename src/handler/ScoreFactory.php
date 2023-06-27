<?php

namespace Kitmap\handler;

use BadFunctionCallException;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\task\repeat\event\DominationTask;
use Kitmap\task\repeat\event\KothPointsTask;
use Kitmap\Util;
use OutOfBoundsException;
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

    private const MIN_LINES = 1;
    private const MAX_LINES = 15;

    private static array $scoreboards = [];

    public static function updateScoreboard(Player $player): void
    {
        $session = Session::get($player);

        if (!$session->data["scoreboard"]) {
            return;
        }

        if (self::hasScore($player)) {
            if (DominationTask::$currentDomination) {
                self::setScore($player, "§eDomination (§7" . strftime("%H:%M") . "§e)");
                $lines = DominationTask::getScoreboardLines();
            } elseif (is_numeric(KothPointsTask::$currentKothPoints)) {
                self::setScore($player, "§eNitro (§7" . strftime("%H:%M") . "§e)");
                $lines = KothPointsTask::getScoreboardLines();
            } else {
                self::setScore($player, "§eNitro (§7" . strftime("%H:%M") . "§e)");

                $rank = ($player->getName() === $player->getDisplayName()) ? ucfirst(strtolower($session->data["rank"])) : "Joueur";
                $faction = Faction::hasFaction($player) ? Faction::getFactionUpperName($session->data["faction"]) : "Aucune";

                $money = Util::formatNumberWithSuffix($session->data["money"]);
                $voteparty = Cache::$data["voteparty"];

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
            self::setScore($player, "§eNitro (§7" . strftime("%H:%M") . "§e)");
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
        if (!isset(self::$scoreboards[mb_strtolower($player->getXuid())])) {
            throw new BadFunctionCallException("Cannot set a score to a player without a scoreboard");
        } elseif ($line < self::MIN_LINES || $line > self::MAX_LINES) {
            throw new OutOfBoundsException("$line is out of range, expected value between " . self::MIN_LINES . " and " . self::MAX_LINES);
        }
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