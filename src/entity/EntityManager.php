<?php

namespace Kitmap\entity;

use Kitmap\entity\entities\AntiBackBallEntity;
use Kitmap\entity\entities\BetEntity;
use Kitmap\entity\entities\floating\BaseFloating;
use Kitmap\entity\entities\floating\LeaderboardsFloating;
use Kitmap\entity\entities\ForgeronEntity;
use Kitmap\entity\entities\LogoutEntity;
use Kitmap\entity\entities\MessageEntity;
use Kitmap\entity\entities\MinerEntity;
use Kitmap\entity\entities\NexusEntity;
use Kitmap\entity\entities\SwitcherEntity;
use Kitmap\entity\entities\TopEntity;
use Kitmap\handler\PackAPI;
use pocketmine\data\bedrock\EntityLegacyIds;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\particle\HugeExplodeSeedParticle;
use pocketmine\world\Position;
use pocketmine\world\sound\BellRingSound;
use pocketmine\world\World;

class EntityManager
{
    public static function startup(): void
    {
        EntityFactory::getInstance()->register(MessageEntity::class, function (World $world, CompoundTag $nbt): MessageEntity {
            return new MessageEntity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["Message"]);

        EntityFactory::getInstance()->register(BaseFloating::class, function (World $world, CompoundTag $nbt): BaseFloating {
            return new BaseFloating(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["BaseFloating"]);

        EntityFactory::getInstance()->register(LeaderboardsFloating::class, function (World $world, CompoundTag $nbt): LeaderboardsFloating {
            return new LeaderboardsFloating(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["LeaderboardsFloating"]);

        EntityFactory::getInstance()->register(AntiBackBallEntity::class, function (World $world, CompoundTag $nbt): AntiBackBallEntity {
            return new AntiBackBallEntity(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
        }, ["AntiBackBallEntity"], EntityLegacyIds::SNOWBALL);

        EntityFactory::getInstance()->register(ForgeronEntity::class, function (World $world, CompoundTag $nbt): ForgeronEntity {
            return new ForgeronEntity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["ForgeronEntity"]);

        EntityFactory::getInstance()->register(LogoutEntity::class, function (World $world, CompoundTag $nbt): LogoutEntity {
            return new LogoutEntity(EntityDataHelper::parseLocation($nbt, $world), LogoutEntity::parseSkinNBT($nbt), $nbt);
        }, ["LogoutEntity"]);

        EntityFactory::getInstance()->register(MinerEntity::class, function (World $world, CompoundTag $nbt): MinerEntity {
            return new MinerEntity(EntityDataHelper::parseLocation($nbt, $world), MinerEntity::parseSkinNBT($nbt), $nbt);
        }, ["MinerEntity"]);

        EntityFactory::getInstance()->register(NexusEntity::class, function (World $world, CompoundTag $nbt): NexusEntity {
            return new NexusEntity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["NexusEntity"], EntityLegacyIds::ENDER_CRYSTAL);

        EntityFactory::getInstance()->register(SwitcherEntity::class, function (World $world, CompoundTag $nbt): SwitcherEntity {
            return new SwitcherEntity(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
        }, ["SwitcherEntity"], EntityLegacyIds::SNOWBALL);

        EntityFactory::getInstance()->register(TopEntity::class, function (World $world, CompoundTag $nbt): TopEntity {
            return new TopEntity(EntityDataHelper::parseLocation($nbt, $world), TopEntity::parseSkinNBT($nbt), $nbt);
        }, ["TopEntity"]);

        EntityFactory::getInstance()->register(BetEntity::class, function (World $world, CompoundTag $nbt): BetEntity {
            return new BetEntity(EntityDataHelper::parseLocation($nbt, $world), BetEntity::parseSkinNBT($nbt), $nbt);
        }, ["BetEntity"]);
    }

    public static function dropItems(Position $position, int $number = 30): void
    {
        $world = $position->getWorld();

        $world->addParticle($position, new HugeExplodeSeedParticle());
        $world->addSound($position, new BellRingSound());

        $centerVector = new Vector3($position->getX(), $position->getY(), $position->getZ());

        foreach (PackAPI::getRandomItems($number) as $item) {
            if ($item instanceof Item) {
                $world->dropItem($centerVector, $item, (new Vector3(mt_rand(-5, 5), 10, mt_rand(-5, 5)))->normalize());
            }
        }
    }
}