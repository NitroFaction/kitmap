<?php

namespace NCore;

use CortexPE\Commando\PacketHooker;
use muqsit\invmenu\InvMenuHandler;
use NCore\command\CommandManager;
use NCore\entity\EntityManager;
use NCore\handler\Cache;
use NCore\listener\ItemListener;
use NCore\listener\PlayerListener;
use NCore\listener\WorldListener;
use NCore\task\repeat\BaseTask;
use NCore\task\repeat\TimeTask;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use ref\libNpcDialogue\libNpcDialogue;

class Base extends PluginBase
{
    use SingletonTrait;

    public function getFile(): string
    {
        return parent::getFile();
    }

    protected function onLoad(): void
    {
        date_default_timezone_set("Europe/Paris");

        Base::setInstance($this);
        Cache::loadCache();
    }

    protected function onEnable(): void
    {
        if (!InvMenuHandler::isRegistered()) InvMenuHandler::register($this);
        if (!PacketHooker::isRegistered()) PacketHooker::register($this);
        if (!libNpcDialogue::isRegistered()) libNpcDialogue::register($this);

        $this->getScheduler()->scheduleRepeatingTask(new BaseTask(), 20);
        $this->getScheduler()->scheduleRepeatingTask(new TimeTask(), 50 * 20);

        $this->getServer()->getPluginManager()->registerEvents(new ItemListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new WorldListener(), $this);

        $this->getServer()->getWorldManager()->getDefaultWorld()->setTime(12750);
        $this->getServer()->getWorldManager()->getDefaultWorld()->stopTime();

        $this->getServer()->getWorldManager()->loadWorld("farm");

        CommandManager::startup();
        EntityManager::startup();
    }

    protected function onDisable(): void
    {
        Cache::saveCache();

        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            $session = Session::get($player);
            $session->saveSessionData();
        }
    }
}