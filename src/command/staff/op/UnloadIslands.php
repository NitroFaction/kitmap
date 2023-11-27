<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\BaseCommand;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\world\World;

class UnloadIslands extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "unloadislands",
            "Permet de décharger toutes les iles inactives"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $count = 0;
        $message = $sender instanceof Player;

        $worlds = array_filter(Main::getInstance()->getServer()->getWorldManager()->getWorlds(), function (World $world) {
            return str_starts_with($world->getFolderName(), "island-");
        });

        if (1 > count($worlds)) {
            if ($message) {
                $sender->sendMessage(Util::PREFIX . "Aucune ile vide à décharger");
            }
            return;
        }

        foreach ($worlds as $world) {
            if ($world instanceof World && 1 > count($world->getPlayers())) {
                $count++;
                Main::getInstance()->getServer()->getWorldManager()->unloadWorld($world, true);
            }
        }

        if ($message) {
            $sender->sendMessage(Util::PREFIX . "Vous venez de décharger §q" . $count . " §file(s) vide(s)");
        }
    }

    protected function prepare(): void
    {
    }
}