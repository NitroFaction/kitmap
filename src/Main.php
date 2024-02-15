<?php

namespace Kitmap;

use CortexPE\Commando\PacketHooker;
use Kitmap\block\ExtraVanillaBlocks;
use Kitmap\command\Commands;
use Kitmap\entity\Entities;
use Kitmap\handler\Cache;
use Kitmap\handler\Rank;
use Kitmap\item\ExtraVanillaItems;
use Kitmap\listener\EventsListener;
use Kitmap\task\repeat\child\GamblingTask;
use Kitmap\task\repeat\PlayerTask;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;

class Main extends PluginBase
{
    use SingletonTrait;

    protected function onLoad(): void
    {
        date_default_timezone_set("Europe/Paris");
        self::setInstance($this);
    }

    protected function onEnable(): void
    {
        new Cache();

        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }
        if (!PacketHooker::isRegistered()) {
            PacketHooker::register($this);
        }

        new Rank();
        new Commands();
        new Entities();

        new ExtraVanillaBlocks();
        new ExtraVanillaItems();

        $this->getScheduler()->scheduleRepeatingTask(new PlayerTask(), 20);
        $this->getServer()->getPluginManager()->registerEvents(new EventsListener(), $this);

        $this->getServer()->getWorldManager()->getDefaultWorld()->setTime(13200);
        $this->getServer()->getWorldManager()->getDefaultWorld()->stopTime();

        $this->getServer()->getWorldManager()->loadWorld("mine");
    }

    protected function onDisable(): void
    {
        PlayerTask::updateBlocks(true);
        GamblingTask::stop();

        Cache::getInstance()->saveAll();

        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            Session::get($player)->saveSessionData();
        }
    }
}
