<?php

namespace Kitmap\task\repeat;

use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use MaXoooZ\Util\entity\MessageEntity;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class MoneyZoneTask
{
    public static function run(): void
    {
        $players = Main::getInstance()->getServer()->getOnlinePlayers();
        $insides = [];

        foreach ($players as $player) {
            if (!$player->isCreative() && $player->isAlive()) {
                if (Util::insideZone($player->getPosition(), "money-zone")) {
                    $insides[] = $player;
                }
            }
        }

        if (count($insides) > 1 || 1 > count($insides)) {
            self::setBlock(false);
            return;
        } else {
            self::setBlock(true);
        }

        $player = $insides[0];

        if ($player instanceof Player) {
            $session = Session::get($player);

            $entity = new MessageEntity($player->getLocation());
            $entity->initEntityB("§e+ 50$");
            $entity->spawnToAll();
            $entity->setMotion(new Vector3(0, 0.15, 0));

            $session->addValue("money", 50);
        }
    }

    private static function setBlock(bool $claimed): void
    {
        $world = Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld();
        $block = $claimed ? VanillaBlocks::GOLD() : VanillaBlocks::IRON();

        $y = 62;

        for ($x = -2; $x <= 0; $x++) {
            for ($z = -128; $z <= -126; $z++) {
                $world->setBlockAt($x, $y, $z, $block);
            }
        }
    }
}