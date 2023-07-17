<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;


use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\Util;
use MaXoooZ\Util\entity\Player;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;
use pocketmine\world\format\Chunk;

class AddClaims extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "addclaims",
            "Permet d'ajouter des claims"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $x1 = intval($args["x1"]);
            $z1 = intval($args["z1"]);
            $x2 = intval($args["x2"]);
            $z2 = intval($args["z2"]);

            $minX = min($x1, $x2);
            $minZ = min($z1, $z2);

            $maxX = max($x1, $x2);
            $maxZ = max($z1, $z2);

            $claims = 0;

            if (!isset(Cache::$data["claims"])) {
                Cache::$data["claims"] = [];
            }

            for ($x = $minX; $x <= $maxX; $x++) {
                for ($z = $minZ; $z <= $maxZ; $z++) {
                    $chunkX = $x >> Chunk::COORD_BIT_SIZE;
                    $chunkZ = $z >> Chunk::COORD_BIT_SIZE;

                    $chunk = $chunkX . ":" . $chunkZ;

                    if (!in_array($chunk, Cache::$data["claims"])) {
                        $claims++;
                        Cache::$data["claims"][] = $chunk;
                    }
                }
            }

            $sender->sendMessage(Util::PREFIX . "§e" . $claims . " §fclaims ajoutés");
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new IntegerArgument("x1"));
        $this->registerArgument(1, new IntegerArgument("z1"));
        $this->registerArgument(2, new IntegerArgument("x2"));
        $this->registerArgument(3, new IntegerArgument("z2"));
    }
}