<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\BaseCommand;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;

class ChangeBiome extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "changebiome",
            "Change le biome des chunks autour du spawn"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            return;
        }

        $id = $args["biome"];
        $around = $args["around"];

        $chunks = [];

        for ($x = -$around; $x <= $around; $x++) {
            for ($z = -$around; $z <= $around; $z++) {
                $chunks[] = [$x, $z];
            }
        }

        foreach ($chunks as $chunk) {
            $chunkX = $chunk[0];
            $chunkZ = $chunk[1];

            $chunk = $sender->getWorld()->loadChunk($chunkX, $chunkZ);

            if (!$chunk instanceof Chunk) {
                continue;
            }

            for ($x = 0; $x < 16; ++$x) {
                for ($y = World::Y_MIN; $y < World::Y_MAX; ++$y) {
                    for ($z = 0; $z < 16; ++$z) {
                        $chunk->setBiomeId($x, $y, $z, intval($id));
                    }
                }
            }
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new IntegerArgument("biome"));
        $this->registerArgument(1, new IntegerArgument("around"));
    }
}