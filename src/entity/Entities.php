<?php

namespace Kitmap\entity;

use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;
use pocketmine\item\enchantment;

class Entities
{
    public function __construct()
    {
        EntityFactory::getInstance()->register(AntiBackBallEntity::class, function (World $world, CompoundTag $nbt): AntiBackBallEntity {
            return new AntiBackBallEntity(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
        }, ["AntiBackBallEntity"]);

        EntityFactory::getInstance()->register(LogoutEntity::class, function (World $world, CompoundTag $nbt): LogoutEntity {
            return new LogoutEntity(EntityDataHelper::parseLocation($nbt, $world), LogoutEntity::parseSkinNBT($nbt), $nbt);
        }, ["LogoutEntity"]);

        EntityFactory::getInstance()->register(NexusEntity::class, function (World $world, CompoundTag $nbt): NexusEntity {
            return new NexusEntity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["NexusEntity"]);

        EntityFactory::getInstance()->register(ElevatorPhantom::class, function (World $world, CompoundTag $nbt): ElevatorPhantom {
            return new ElevatorPhantom(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["ElevatorPhantom"]);

        EntityFactory::getInstance()->register(SwitcherEntity::class, function (World $world, CompoundTag $nbt): SwitcherEntity {
            return new SwitcherEntity(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
        }, ["SwitcherEntity"]);

        EntityFactory::getInstance()->register(FloatingText::class, function (World $world, CompoundTag $nbt): FloatingText {
            return new FloatingText(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["FloatingText"]);

        EntityFactory::getInstance()->register(Forgeron::class, function (World $world, CompoundTag $nbt): Forgeron {
            return new Forgeron(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["Forgeron"]);
    }
}