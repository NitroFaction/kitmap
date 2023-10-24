<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\BaseCommand;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\world\format\Chunk;

class ChunkInfo extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "chunkinfo",
            "Permet d'avoir les coordonnées du chunk actuel"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $chunkX = $sender->getPosition()->getFloorX() >> Chunk::COORD_BIT_SIZE;
            $chunkZ = $sender->getPosition()->getFloorZ() >> Chunk::COORD_BIT_SIZE;

            $sender->sendMessage(Util::PREFIX . $chunkX . "§e:§f" . $chunkZ);
        }
    }

    protected function prepare(): void
    {
    }
}
