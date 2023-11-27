<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\Main;
use pocketmine\command\CommandSender;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;

class EnableSnow extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "enablesnow",
            "Active la neige au spawn"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $world = Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld();
        [$x1, $y1, $z1, $x2, $y2, $z2] = explode(":", Cache::$config["zones"]["spawn"]);

        $minX = min($x1, $x2);
        $minY = min($y1, $y2);
        $minZ = min($z1, $z2);

        $maxX = max($x1, $x2);
        $maxY = max($y1, $y2);
        $maxZ = max($z1, $z2);

        for ($x = 0; $minX < $maxX; ++$x) {
            for ($y = 0; $minY < $maxY; ++$y) {
                for ($z = 0; $minZ < $maxZ; ++$z) {
                    $world->setBiomeId($x, $y, $z, BiomeIds::ICE_PLAINS);
                }
            }
        }
    }

    protected function prepare(): void
    {
    }
}