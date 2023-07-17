<?php

namespace NCore\handler;

use NCore\Base;
use NCore\Session;
use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\color\Color;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\format\Chunk;
use pocketmine\world\particle\DustParticle;
use pocketmine\world\Position;
use pocketmine\world\World;
use Util\util\IdsUtils;
use Webmozart\PathUtil\Path;

class OtherAPI
{
    public static function getItemCount(Player $player, int $id, int $meta = 0): int
    {
        return self::getInventoryItemCount($player->getInventory(), $id, $meta);
    }

    public static function getInventoryItemCount(Inventory $inventory, int $id, int $meta = 0): int
    {
        $count = 0;

        foreach ($inventory->getContents() as $item) {
            if (is_null($item->getNamedTag()->getTag("partneritem"))) {
                if ($item->getId() === $id && $item->getMeta() === $meta) {
                    $count += $item->getCount();
                }
            }
        }
        return $count;
    }

    public static function listAllFiles(string $dir): array
    {
        $array = scandir($dir);
        $result = [];

        foreach ($array as $value) {
            $currentPath = Path::join($dir, $value);

            if ($value === "." || $value === '..') {
                continue;
            } else if (is_file($currentPath)) {
                $result[] = $currentPath;
                continue;
            }

            foreach (self::listAllFiles($currentPath) as $_value) {
                $result[] = $_value;
            }
        }
        return $result;
    }

    public static function isElevatorBlock(int $x, int $y, int $z, World $world): ?Block
    {
        $elevator = $world->getBlockAt($x, $y, $z);

        if ($elevator->getId() !== IdsUtils::ELEVATOR_BLOCK) {
            return null;
        }
        return $elevator;
    }

    public static function getTwoBlocksAvaible(Block $block): bool
    {
        $position = $block->getPosition();
        $world = $position->getWorld();

        $block1 = $world->getBlock($position->add(0, 1, 0));
        $block2 = $world->getBlock($position->add(0, 2, 0));

        if ($block1->getId() !== BlockLegacyIds::AIR || $block2->getId() !== BlockLegacyIds::AIR) {
            return false;
        }
        return true;
    }

    public static function addBorderParticles(Player $player): void
    {
        $position = $player->getPosition()->asVector3();

        $chunkX = $position->getFloorX() >> Chunk::COORD_BIT_SIZE;
        $chunkZ = $position->getFloorZ() >> Chunk::COORD_BIT_SIZE;

        $minX = (float)$chunkX * 16;
        $maxX = $minX + 16;

        $minZ = (float)$chunkZ * 16;
        $maxZ = $minZ + 16;

        $r = mt_rand(0, 255);
        $g = mt_rand(0, 255);
        $b = mt_rand(0, 255);

        for ($x = $minX; $x <= $maxX; $x += 0.5) {
            for ($z = $minZ; $z <= $maxZ; $z += 0.5) {
                if ($x === $minX || $x === $maxX || $z === $minZ || $z === $maxZ) {
                    $vector = new Vector3($x, $position->getY() + 0.8, $z);

                    if ($player->getWorld()->isLoaded() && $player->getWorld()->isInLoadedTerrain($vector)) {
                        $player->getWorld()->addParticle($vector, new DustParticle(new Color($r, $g, $b)), [$player]);
                    }
                }
            }
        }
    }

    public static function getPlace(Player $player): int
    {
        return floor($player->getPosition()->getX() + $player->getPosition()->getY() + $player->getPosition()->getZ());
    }

    public static function addItem(Player $player, Item $item, bool $noDrop = false): void
    {
        if (!$noDrop && !$player->getInventory()->canAddItem($item)) {
            $player->getWorld()->dropItem($player->getPosition()->asVector3(), $item);
        }

        $player->getInventory()->addItem($item);
    }

    public static function getTpTime(Player $player): int
    {
        $session = Session::get($player);

        if (!$player->isAlive() || $player->isCreative() || $session->data["player"]["staff_mod"][0] || $player->getWorld() !== Base::getInstance()->getServer()->getWorldManager()->getDefaultWorld() || self::insideZone($player->getPosition(), "spawn")) {
            return -1;
        } else if ($session->data["player"]["rank"] !== "joueur") {
            return 3;
        } else {
            return 5;
        }
    }

    public static function insideZone(Position $position, string $zone): bool
    {
        list ($x1, $y1, $z1, $x2, $y2, $z2, $world) = explode(":", Cache::$config["zones"][$zone]);

        $minX = min($x1, $x2);
        $minY = min($y1, $y2);
        $minZ = min($z1, $z2);

        $maxX = max($x1, $x2);
        $maxY = max($y1, $y2);
        $maxZ = max($z1, $z2);

        $x = $position->getFloorX();
        $y = $position->getFloorY();
        $z = $position->getFloorZ();

        return $x >= $minX && $x <= $maxX && $y >= $minY && $y <= $maxY && $z >= $minZ && $z <= $maxZ && $position->getWorld()->getFolderName() === $world;
    }

    public static function format(int $value): string
    {
        $value = (0 + str_replace(",", "", $value));

        if ($value > 1000000000000) {
            return round(($value / 1000000000000), 2) . "MD";
        } else if ($value > 1000000000) {
            return round(($value / 1000000000), 2) . "B";
        } else if ($value > 1000000) {
            return round(($value / 1000000), 2) . "M";
        } else if ($value > 1000) {
            return round(($value / 1000), 2) . "k";
        } else {
            return number_format($value);
        }
    }
}