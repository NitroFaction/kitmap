<?php

namespace Kitmap\block;

use Kitmap\command\player\rank\Craft;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\type\graphic\InvMenuGraphic;
use muqsit\invmenu\type\InvMenuType;
use muqsit\invmenu\type\util\InvMenuTypeBuilders;
use pocketmine\block\inventory\CraftingTableInventory;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\Inventory;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\inventory\WindowTypes;
use pocketmine\player\Player;
use pocketmine\world\Position;

class World
{
    public function __construct()
    {
        $class = new class implements InvMenuType {
            private InvMenuType $inner;

            public function __construct()
            {
                $this->inner = InvMenuTypeBuilders::BLOCK_FIXED()
                    ->setBlock(VanillaBlocks::CRAFTING_TABLE())
                    ->setSize(9)
                    ->setNetworkWindowType(WindowTypes::WORKBENCH)
                    ->build();
            }

            public function createGraphic(InvMenu $menu, Player $player): ?InvMenuGraphic
            {
                return $this->inner->createGraphic($menu, $player);
            }

            public function createInventory(): Inventory
            {
                return new CraftingTableInventory(Position::fromObject(Vector3::zero(), null));
            }
        };


        InvMenuHandler::getTypeRegistry()->register(Craft::INV_MENU_TYPE_WORKBENCH, $class);
    }
}