<?php

namespace Kitmap\task\repeat;

use Kitmap\handler\Cache;
use Kitmap\handler\ScoreFactory;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\world\Position;
use WeakMap;

class PlayerTask extends Task
{
    public static array $blocks = [];

    /* @var WeakMap<Player, Vector3> */
    private static WeakMap $lastPosition;
    private int $tick = 0;

    public function __construct()
    {
        self::$lastPosition = new WeakMap();
    }

    public function onRun(): void
    {
        $this->tick++;
        $tick = $this->tick;

        DominationTask::run();
        KothTask::run();
        GamblingTask::run();
        OutpostTask::run();

        if ($this->tick % 3 == 0) {
            MoneyZoneTask::run();
        }

        if ($this->tick % 250 == 0) {
            $messages = Cache::$config["messages"];
            Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . $messages[array_rand($messages)]);
        }

        self::updateBlocks();

        foreach (Cache::$combatPlayers as $player => $ignore) {
            if (!$player instanceof Player || !$player->isConnected() || in_array($player->getName(), GamblingTask::$players)) {
                continue;
            }

            $session = Session::get($player);
            $position = $player->getPosition();

            if ($session->inCooldown("combat")) {
                if ($player->getWorld() !== Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()) {
                    continue;
                }

                if (Util::insideZone($position, "spawn")) {
                    if (isset(self::$lastPosition[$player])) {
                        $player->teleport(self::$lastPosition[$player]);
                    }
                }
            } else {
                $player->sendMessage(Util::PREFIX . "Vous n'êtes désormais plus en combat");
                unset(Cache::$combatPlayers[$player]);
            }

            if (!Util::insideZone($position, "spawn")) {
                self::$lastPosition[$player] = $position->asVector3();
            }
        }

        foreach (Cache::$borderPlayers as $player => $ignore) {
            if ($player->isConnected() && $tick % 2 == 0) {
                Util::addBorderParticles($player);
            }
        }

        foreach (Cache::$scoreboardPlayers as $player => $ignore) {
            if ($player->isConnected() && $tick % (DominationTask::$currentDomination ? 1 : 45) == 0) {
                ScoreFactory::updateScoreboard($player);
            }
        }

        foreach (Cache::$config["interval"] as $ticks => $command) {
            if ($tick % intval($ticks) == 0) {
                Util::executeCommand($command);
            }
        }

        if ($tick % 50 == 0) {
            $time = date("H:i");

            if (isset(Cache::$config["planning"][$time])) {
                Util::executeCommand(Cache::$config["planning"][$time]);
            }

            if (($h = intval(explode(":", $time)[0])) >= 12 && $h <= 24) {
                if (intval(explode(":", $time)[1]) === 0 && ($h - 13) % 2 === 0) {
                    Util::executeCommand("nexus start");
                } else if (intval(explode(":", $time)[1]) === 30) {
                    Util::executeCommand("koth start");
                }
            }
        }
    }

    public static function updateBlocks(bool $force = false): void
    {
        foreach (self::$blocks as $index => [$time, $position, $block]) {
            if (!is_int($time) || !$position instanceof Position || !$position->isValid() || !$block instanceof Block) {
                unset(self::$blocks[$index]);
            }

            if ($force || time() >= $time) {
                $position->getWorld()->setBlock($position, $block, false);
                unset(self::$blocks[$index]);
            }
        }
    }
}