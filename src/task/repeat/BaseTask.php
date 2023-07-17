<?php

namespace NCore\task\repeat;

use NCore\Base;
use NCore\handler\Cache;
use NCore\handler\FactionAPI;
use NCore\handler\OtherAPI;
use NCore\handler\ScoreFactory;
use NCore\Session;
use NCore\task\repeat\event\DominationTask;
use NCore\task\repeat\event\KothPointsTask;
use NCore\task\repeat\event\KothTask;
use NCore\task\repeat\event\OutpostTask;
use NCore\Util;
use pocketmine\color\Color;
use pocketmine\data\bedrock\EffectIdMap;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\inventory\ArmorInventory;
use pocketmine\player\GameMode;
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
        $players = Base::getInstance()->getServer()->getOnlinePlayers();

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
                    if ($player->getWorld() !== Base::getInstance()->getServer()->getWorldManager()->getDefaultWorld()) {
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

            if (!OtherAPI::insideZone($position, "spawn")) {
                self::$lastPosition[$player->getName()] = $position->asVector3();
            }

            if ($session->inCooldown("_effectdisabler")) {
                $player->getEffects()->clear();
                $player->sendTip(Util::PREFIX . "Effect Disabler actif: §e" . $session->getCooldownData("_effectdisabler")[0] - time());
            }

            foreach ($player->getArmorInventory()->getContents() as $key => $item) {
                if (
                    $item instanceof Armor &&
                    in_array($item->getId(), [IdsUtils::RAINBOW_HELMET, IdsUtils::RAINBOW_CHESTPLATE, IdsUtils::RAINBOW_LEGGINGS, IdsUtils::RAINBOW_BOOTS])
                ) {
                    $item = $item->setCustomColor(new Color(mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)));
                    $player->getArmorInventory()->setItem($key, $item);
                }
            }

            if ($session->data["player"]["night_vision"]) {
                $player->getEffects()->add(new EffectInstance(VanillaEffects::NIGHT_VISION(), 30 * 20, 255, false));
            }

            foreach (Cache::$config["atouts"] as $name => $atout) {
                $enabled = $session->data["player"]["atouts"][$name][0] ?? false;

                if ($enabled) {
                    $player->getEffects()->add(new EffectInstance(EffectIdMap::getInstance()->fromId($atout["id"]), 30 * 20, 0, false));
                }
            }

            $claim = FactionAPI::inPlot($x, $z);

            if ($player->getWorld() === Base::getInstance()->getServer()->getWorldManager()->getDefaultWorld() && $claim[0] && $claim[1] !== $session->data["player"]["faction"]) {
                if ($player->getGamemode() === GameMode::SURVIVAL()) {
                    $player->setGamemode(GameMode::ADVENTURE());
                }
            } else {
                if ($player->getGamemode() === GameMode::ADVENTURE()) {
                    $player->setGamemode(GameMode::SURVIVAL());
                }
            }

            if ($player->getWorld() === Base::getInstance()->getServer()->getWorldManager()->getDefaultWorld()) {
                if ($claim !== $session->data["claim"]) {
                    $name = in_array($session->data["claim"][1], ["Aucune faction", "Nature"]) ? "Nature" : FactionAPI::getFactionUpperName($session->data["claim"][1]);
                    $_name = in_array($claim[1], ["Aucune faction", "Nature"]) ? "Nature" : FactionAPI::getFactionUpperName($claim[1]);

                    if ($name !== $_name) {
                        $player->sendTip("§f" . $name . " " . Util::PREFIX . $_name);
                    }

                    $session->data["claim"] = $claim;
                }
            }

            if ($player->getArmorInventory()->getItem(ArmorInventory::SLOT_HEAD)->getId() === IdsUtils::FARM_HELMET && $player->getArmorInventory()->getItem(ArmorInventory::SLOT_CHEST)->getId() === IdsUtils::FARM_CHESTPLATE && $player->getArmorInventory()->getItem(ArmorInventory::SLOT_LEGS)->getId() === IdsUtils::FARM_LEGGINGS && $player->getArmorInventory()->getItem(ArmorInventory::SLOT_FEET)->getId() === IdsUtils::FARM_BOOTS) {
                $player->getEffects()->add(new EffectInstance(VanillaEffects::FIRE_RESISTANCE(), 30 * 5, 0, false));
                $player->getEffects()->add(new EffectInstance(VanillaEffects::HASTE(), 30 * 5, 2, false));
                $player->getEffects()->add(new EffectInstance(VanillaEffects::JUMP_BOOST(), 30 * 5, 1, false));
                $player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 30 * 5, 1, false));
            }

            if (!str_starts_with($player->getWorld()->getFolderName(), "island-")) {
                if (!$session->data["player"]["staff_mod"][0] && !$player->isCreative()) {
                    $player->setAllowFlight(false);
                    $player->setFlying(false);
                }
            }

            $player->getHungerManager()->setFood(18);

            $session->data["player"]["ping"][] = $player->getNetworkSession()->getPing();

            if (count($session->data["player"]["ping"]) > 5) {
                array_shift($session->data["player"]["ping"]);
            }

            if ($session->data["player"]["border"] && $this->tick % 3 == 0) {
                OtherAPI::addBorderParticles($player);
            }

            if ($this->tick % (DominationTask::$currentDomination || is_numeric(KothPointsTask::$currentKothPoints) ? 1 : 15) == 0) {
                ScoreFactory::updateScoreboard($player);
            }
        }
    }
}