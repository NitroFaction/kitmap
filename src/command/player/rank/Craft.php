<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player\rank;

use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Rank;
use Kitmap\Session;
use Kitmap\Util;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\type\graphic\InvMenuGraphic;
use muqsit\invmenu\type\InvMenuType;
use muqsit\invmenu\type\util\InvMenuTypeBuilders;
use pocketmine\block\inventory\CraftingTableInventory;
use pocketmine\block\VanillaBlocks;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\inventory\WindowTypes;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\world\Position;

class Craft extends BaseCommand
{
    public const INV_MENU_TYPE_WORKBENCH = "portablecrafting:workbench";

    public function __construct(PluginBase $plugin)
    {
        InvMenuHandler::getTypeRegistry()->register(self::INV_MENU_TYPE_WORKBENCH, new class implements InvMenuType {
            private InvMenuType $inner;

            public function __construct()
            {
                $this->inner = InvMenuTypeBuilders::BLOCK_FIXED()
                    ->setBlock(VanillaBlocks::CRAFTING_TABLE())->setSize(9)
                    ->setNetworkWindowType(WindowTypes::WORKBENCH)->build();
            }

            public function createGraphic(InvMenu $menu, Player $player): ?InvMenuGraphic
            {
                return $this->inner->createGraphic($menu, $player);
            }

            public function createInventory(): CraftingTableInventory
            {
                return new CraftingTableInventory(Position::fromObject(Vector3::zero(), null));
            }
        });

        parent::__construct(
            $plugin,
            "craft",
            "Ouvre un établi n'importe où"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->data["staff_mod"][0] || $sender->getGamemode() === GameMode::SPECTATOR()) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas accèder à votre enderchest en étant en staff mod");
                return;
            } else if (!Rank::hasRank($sender, "prince")) {
                $sender->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de faire cela");
                return;
            }

            InvMenu::create(self::INV_MENU_TYPE_WORKBENCH)->send($sender);
        }
    }

    protected function prepare(): void
    {
    }
}