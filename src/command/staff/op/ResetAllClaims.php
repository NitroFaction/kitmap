<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use MaXoooZ\Util\entity\Player;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;

class ResetAllClaims extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "resetallclaims",
            "Reset tout les claims"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            Cache::$claims = [];

            foreach (Cache::$factions as $name => $ignore) {
                Cache::$factions[$name]["claim"] = null;
            }
        }
    }

    protected function prepare(): void
    {
    }
}