<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class SetBooster extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "setbooster",
            "Défini un joueur comme booster discord ou non"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $username = strtolower($args["pseudo"]);

        /** @noinspection PhpDeprecationInspection */
        $player = Main::getInstance()->getServer()->getPlayerByPrefix($username);

        if ($player instanceof Player) {
            $session = Session::get($player);
            $session->data["boost"] = [time(), false];
        } else {
            if (isset(Cache::$players["upper_name"][$username])) {
                $file = Util::getFile("data/players/" . $username);

                $file->set("boost", [time(), false]);
                $file->save();
            }
        }

        $sender->sendMessage(Util::PREFIX . "Vous venez de définir le joueur §e" . $username . " §fcomme booster");
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("pseudo"));
    }
}