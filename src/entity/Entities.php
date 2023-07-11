<?php

namespace Kitmap\entity;

use Kitmap\handler\Pack;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\particle\HugeExplodeSeedParticle;
use pocketmine\world\Position;
use pocketmine\world\sound\BellRingSound;
use pocketmine\world\World;

class Entities {
	public function __construct() {
		EntityFactory::getInstance()->register(AntiBackBallEntity::class, function(World $world, CompoundTag $nbt) : AntiBackBallEntity {
			return new AntiBackBallEntity(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
		}, [ "AntiBackBallEntity" ]);

		EntityFactory::getInstance()->register(LogoutEntity::class, function(World $world, CompoundTag $nbt) : LogoutEntity {
			return new LogoutEntity(EntityDataHelper::parseLocation($nbt, $world), LogoutEntity::parseSkinNBT($nbt), $nbt);
		}, [ "LogoutEntity" ]);

		EntityFactory::getInstance()->register(NexusEntity::class, function(World $world, CompoundTag $nbt) : NexusEntity {
			return new NexusEntity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
		}, [ "NexusEntity" ]);

		EntityFactory::getInstance()->register(SwitcherEntity::class, function(World $world, CompoundTag $nbt) : SwitcherEntity {
			return new SwitcherEntity(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
		}, [ "SwitcherEntity" ]);
	}

	public static function dropItems(Position $position, int $number = 30) : void {
		$world = $position->getWorld();

		$world->addParticle($position, new HugeExplodeSeedParticle());
		$world->addSound($position, new BellRingSound());

		$centerVector = new Vector3($position->getX(), $position->getY(), $position->getZ());

		foreach (Pack::getRandomItems($number) as $item) {
			if ($item instanceof Item) {
				$world->dropItem($centerVector, $item, (new Vector3(mt_rand(-5, 5), 10, mt_rand(-5, 5)))->normalize());
			}
		}
	}
}