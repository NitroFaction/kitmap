<?php

namespace Kitmap;

use CortexPE\Commando\PacketHooker;
use muqsit\invmenu\InvMenuHandler;
use Kitmap\command\Commands;
use Kitmap\entity\EntityManager;
use Kitmap\handler\Cache;
use Kitmap\handler\Rank;
use Kitmap\listener\ItemListener;
use Kitmap\listener\PlayerListener;
use Kitmap\listener\WorldListener;
use Kitmap\task\repeat\BaseTask;
use Kitmap\task\repeat\TimeTask;
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

        $this->getScheduler()->scheduleRepeatingTask(new BaseTask(), 20);
        $this->getScheduler()->scheduleRepeatingTask(new TimeTask(), 50 * 20);

        $this->getServer()->getPluginManager()->registerEvents(new ItemListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new WorldListener(), $this);

        $this->getServer()->getWorldManager()->getDefaultWorld()->setTime(12500);
        $this->getServer()->getWorldManager()->getDefaultWorld()->stopTime();

        $this->getServer()->getWorldManager()->loadWorld("farm");

        EntityManager::startup();
    }

    protected function onDisable(): void
    {
        Cache::getInstance()->saveAll();
    }
}