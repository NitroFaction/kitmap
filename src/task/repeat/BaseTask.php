<?php

namespace Kitmap\task\repeat;

use Kitmap\handler\Cache;
use Kitmap\handler\OtherAPI;
use Kitmap\handler\ScoreFactory;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\task\repeat\event\DominationTask;
use Kitmap\task\repeat\event\KothPointsTask;
use Kitmap\task\repeat\event\KothTask;
use Kitmap\task\repeat\event\OutpostTask;
use Kitmap\Util;
use pocketmine\data\bedrock\EffectIdMap;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\scheduler\Task;
use Util\item\items\custom\Armor;
use Util\util\IdsUtils;

class BaseTask extends Task
{
    public static array $combat = [];
    private static array $lastPosition = [];

    private int $tick = 1;

    public function onRun(): void
    {
        $this->tick++;
        $players = Main::getInstance()->getServer()->getOnlinePlayers();

        DominationTask::run();
        KothTask::run();
        OutpostTask::run();
        KothPointsTask::run();

        foreach ($players as $player) {
            if (!$player->isAlive()) {
                continue;
            }

            $session = Session::get($player);
            $position = $player->getPosition();

            $x = $position->getFloorX();
            $z = $position->getFloorZ();

            if (in_array($player->getName(), self::$combat)) {
                if ($session->inCooldown("combat")) {
                    if ($player->getWorld() !== Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()) {
                        continue;
                    }

                    if (OtherAPI::insideZone($position, "spawn")) {
                        if (isset(self::$lastPosition[$player->getName()])) {
                            $player->teleport(self::$lastPosition[$player->getName()]);
                        }
                    }
                } else {
                    $player->sendMessage(Util::PREFIX . "Vous n'êtes désormais plus en combat");

                    if (($key = array_search($player->getName(), self::$combat)) !== false) {
                        unset(self::$combat[$key]);
                    }
                }
            }

            if (!Util::insideZone($position, "spawn")) {
                self::$lastPosition[$player->getName()] = $position->asVector3();
            }

            if ($session->data["night_vision"]) {
                $player->getEffects()->add(new EffectInstance(VanillaEffects::NIGHT_VISION(), 30 * 20, 255, false));
            }

            foreach (Cache::$config["atouts"] as $name => $atout) {
                $enabled = $session->data["atouts"][$name][0] ?? false;

                if ($enabled) {
                    $player->getEffects()->add(new EffectInstance(EffectIdMap::getInstance()->fromId($atout["id"]), 30 * 20, 0, false));
                }
            }

            $player->getHungerManager()->setFood(18);

            if ($session->data["border"] && $this->tick % 3 == 0) {
                Util::addBorderParticles($player);
            }

            if ($this->tick % (DominationTask::$currentDomination || is_numeric(KothPointsTask::$currentKothPoints) ? 1 : 15) == 0) {
                ScoreFactory::updateScoreboard($player);
            }
        }
    }
}