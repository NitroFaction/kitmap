<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;


use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\Util;
use MaXoooZ\Util\entity\Player;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;

class ListClaims extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "listclaims",
            "Permet de donner la liste des claims"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $sender->sendMessage(Util::PREFIX . "Voici la liste des claims: §e" . implode("§f, §e", Cache::$data["claims"]));
        }
    }

    protected function prepare(): void
    {
    }
}