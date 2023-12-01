<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\BaseCommand;
use Kitmap\Main;
use pocketmine\block\VanillaBlocks;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;

class SaveChunk extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "savechunk",
            "Sauvegarde le chunk en un fichier php"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $blocks = [];
            $world = $sender->getPosition()->getWorld();

            $chunkX = $sender->getPosition()->getFloorX() >> Chunk::COORD_BIT_SIZE;
            $chunkZ = $sender->getPosition()->getFloorZ() >> Chunk::COORD_BIT_SIZE;

            $file = new Config(Main::getInstance()->getDataFolder() . $chunkX . "-" . $chunkZ . ".yml", Config::YAML);

            for ($x = 0; $x < 16; ++$x) {
                for ($y = World::Y_MIN; $y < World::Y_MAX; ++$y) {
                    for ($z = 0; $z < 16; ++$z) {
                        $newX = ($chunkX * 16) + $x;
                        $newZ = ($chunkZ * 16) + $z;

                        $block = $world->getBlockAt($newX, $y, $newZ);

                        if (!$block->hasSameTypeId(VanillaBlocks::AIR())) {
                            $blockName = $this->process($block->getName());
                            $blocks[] = "\$world->setBlockAt($newX, $y, $newZ, VanillaBlocks::$blockName());";
                        }
                    }
                }
            }

            $file->set("data", implode("\n", $blocks));
            $file->save();

            $sender->sendMessage($chunkX . "-" . $chunkZ . " -> " . $sender->getPosition()->getFloorX() . ":" . $sender->getPosition()->getFloorY() . ":" . $sender->getPosition()->getFloorZ());
        }
    }

    private function process(string $str): string
    {
        return strtoupper(str_replace([" ", "minecraft:"], ["_", ""], $str));
    }

    protected function prepare(): void
    {
    }
}