<?php /* @noinspection PhpDeprecationInspection */

namespace Kitmap;

use CortexPE\Commando\PacketHooker;
use Kitmap\command\Commands;
use Kitmap\entity\Entities;
use Kitmap\handler\Cache;
use Kitmap\handler\Rank;
use Kitmap\listener\PlayerListener;
use Kitmap\task\repeat\PlayerTask;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\ItemFlags;
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

        EnchantmentIdMap::getInstance()->register(-1, new Enchantment("glow", -1, ItemFlags::ALL, ItemFlags::NONE, 1));

        new Rank();
        new Commands();
        new Entities();

        $this->getScheduler()->scheduleRepeatingTask(new PlayerTask(), 20);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerListener(), $this);

        $this->getServer()->getWorldManager()->getDefaultWorld()->setTime(12500);
        $this->getServer()->getWorldManager()->getDefaultWorld()->stopTime();

        $this->getServer()->getWorldManager()->loadWorld("mine");
    }

    protected function onDisable(): void
    {
        PlayerTask::updateBlocks(true);
        Cache::getInstance()->saveAll();

        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            Session::get($player)->saveSessionData();
        }
    }
}
