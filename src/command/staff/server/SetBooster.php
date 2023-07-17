<?php /** @noinspection PhpUnused */

namespace NCore\command\staff\server;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseCommand;
use NCore\Base;
use NCore\handler\Cache;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
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

        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $username = strtolower($args["pseudo"]);
        $player = Base::getInstance()->getServer()->getPlayerByPrefix($username);

        if ($player instanceof Player) {
            $session = Session::get($player);
            $session->data["player"]["boost"] = [time(), false];
        } else {
            if (isset(Cache::$players["upper_name"][$username])) {
                $file = Util::getFile("players/" . $username);

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