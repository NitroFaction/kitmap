<?php

namespace Kitmap\task\repeat;

use Element\entity\MessageEntity;
use Kitmap\handler\Cache;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\block\utils\DyeColor;
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
            $entity->initEntityB("§q+ 50$");
            $entity->spawnToAll();
            $entity->setMotion(new Vector3(0, 0.15, 0));

            $session->addValue("money", 50);
        }
    }

    private static function setBlock(bool $claimed): void
    {
        $world = Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld();
        $block = $claimed ? VanillaBlocks::CONCRETE_POWDER()->setColor(DyeColor::GREEN()) : VanillaBlocks::CONCRETE_POWDER()->setColor(DyeColor::LIGHT_GRAY());

        [$x1, $y, $z1, $x2, , $z2,] = explode(":", Cache::$config["zones"]["money-zone"]);

        $minX = min($x1, $x2);
        $minZ = min($z1, $z2);

        $maxX = max($x1, $x2);
        $maxZ = max($z1, $z2);

        for ($x = $minX; $x <= $maxX; $x++) {
            for ($z = $minZ; $z <= $maxZ; $z++) {
                $world->setBlockAt($x, intval($y), $z, $block);
            }
        }
    }
}