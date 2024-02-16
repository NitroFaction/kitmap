<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\BaseCommand;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class PlayerInfo extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "playerinfo",
            "Donne quelques infos à propos de son player"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $sender->sendMessage(Util::PREFIX . intval($sender->getLocation()->getYaw()));
            $sender->sendMessage(Util::PREFIX . intval($sender->getLocation()->getPitch()));

            $x = $sender->getLocation()->getFloorX() + 0.5;
            $y = $sender->getLocation()->getFloorY() + 1;
            $z = $sender->getLocation()->getFloorZ() + 0.5;

            $format = $x . ":" . $y . ":" . $z;

            var_dump($format);
            $sender->sendMessage(Util::PREFIX . $format);
        }
    }

    protected function prepare(): void
    {
    }
}
