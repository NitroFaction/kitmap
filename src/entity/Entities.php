<?php

namespace Kitmap\entity;

use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\data\bedrock\PotionTypeIdMap;
use pocketmine\data\bedrock\PotionTypeIds;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;

class Entities
{
    public function __construct()
    {
        EntityFactory::getInstance()->register(AntiBackBall::class, function (World $world, CompoundTag $nbt): AntiBackBall {
            return new AntiBackBall(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
        }, ["AntiBackBallEntity"]);

        EntityFactory::getInstance()->register(LogoutNpc::class, function (World $world, CompoundTag $nbt): LogoutNpc {
            return new LogoutNpc(EntityDataHelper::parseLocation($nbt, $world), LogoutNpc::parseSkinNBT($nbt), $nbt);
        }, ["LogoutEntity"]);

        EntityFactory::getInstance()->register(Nexus::class, function (World $world, CompoundTag $nbt): Nexus {
            return new Nexus(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["NexusEntity"]);

        EntityFactory::getInstance()->register(ElevatorPhantom::class, function (World $world, CompoundTag $nbt): ElevatorPhantom {
            return new ElevatorPhantom(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["ElevatorPhantom"]);

        EntityFactory::getInstance()->register(SwitchBall::class, function (World $world, CompoundTag $nbt): SwitchBall {
            return new SwitchBall(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
        }, ["SwitcherEntity"]);

        EntityFactory::getInstance()->register(DefaultFloatingText::class, function (World $world, CompoundTag $nbt): DefaultFloatingText {
            return new DefaultFloatingText(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["FloatingText"]);

        EntityFactory::getInstance()->register(BlackSmith::class, function (World $world, CompoundTag $nbt): BlackSmith {
            return new BlackSmith(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["Forgeron"]);


        EntityFactory::getInstance()->register(EnderPearl::class, function (World $world, CompoundTag $nbt): EnderPearl {
            return new EnderPearl(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
        }, ["ThrownEnderpearl", "minecraft:ender_pearl"]);

        EntityFactory::getInstance()->register(GhostBlock::class, function (World $world, CompoundTag $nbt): GhostBlock {
            return new GhostBlock(EntityDataHelper::parseLocation($nbt, $world), GhostBlock::parseBlockNBT(RuntimeBlockStateRegistry::getInstance(), $nbt), $nbt);
        }, ["GhostBlock"]);

        EntityFactory::getInstance()->register(Message::class, function (World $world, CompoundTag $nbt): Message {
            return new Message(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["MessageEntity"]);

        EntityFactory::getInstance()->register(SplashPotion::class, function (World $world, CompoundTag $nbt): SplashPotion {
            $potionType = PotionTypeIdMap::getInstance()->fromId($nbt->getShort(SplashPotion::TAG_POTION_ID, PotionTypeIds::WATER));
            if ($potionType === null) throw new SavedDataLoadingException("No such potion type");
            return new SplashPotion(EntityDataHelper::parseLocation($nbt, $world), null, $potionType, $nbt);
        }, ["ThrownPotion", "minecraft:potion", "thrownpotion"]);
    }
}