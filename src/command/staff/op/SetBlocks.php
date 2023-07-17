<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;


use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Rank;
use MaXoooZ\Util\entity\Player;
use pocketmine\block\VanillaBlocks;
use pocketmine\command\CommandSender;
use pocketmine\inventory\CreativeInventory;
use pocketmine\plugin\PluginBase;

class SetBlocks extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "setblocks",
            "Met tout les blocks"
        );

        $this->setPermissions([Rank::GROUP_STAFF]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            if ($sender->getName() !== "MaXoooZ" && $sender->getName() !== "xMyma") {
                return;
            }

            $items = CreativeInventory::getInstance()->getAll();
            $blocks = [];

            foreach ($items as $item) {
                $block = $item->getBlock();

                if (!$block->isSameState(VanillaBlocks::AIR()) && $block->isSolid() && !$block->canBeFlowedInto() && !$block->isSameState(VanillaBlocks::BED())) {
                    $blocks[] = $block;
                }
            }

            $i = 0;
            $side = ceil(sqrt(count($blocks)));

            $playerX = $sender->getPosition()->getFloorX();
            $playerY = $sender->getPosition()->getFloorY() - 1;
            $playerZ = $sender->getPosition()->getFloorZ();

            for ($x = $playerX; $x <= $playerX + $side; $x++) {
                for ($z = $playerZ; $z <= $playerZ + $side; $z++) {
                    $sender->getPosition()->getWorld()->setBlockAt($x, $playerY, $z, $blocks[$i] ?? VanillaBlocks::AIR());
                    $i++;
                }
            }
        }
    }

    protected function prepare(): void
    {
    }
}