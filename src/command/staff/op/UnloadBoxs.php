<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\BaseCommand;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;
use pocketmine\world\World;

class UnloadBoxs extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "unloadboxs",
            "Permet de décharger toutes les boxs inactives"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $count = 0;

        $worlds = array_filter(Main::getInstance()->getServer()->getWorldManager()->getWorlds(), function (World $world) {
            return str_starts_with($world->getFolderName(), "box-");
        });

        if (1 > count($worlds)) {
            $sender->sendMessage(Util::PREFIX . "Aucune box vide à décharger");
            return;
        }

        foreach ($worlds as $world) {
            if ($world instanceof World && 1 > count($world->getPlayers())) {
                $count++;
                Main::getInstance()->getServer()->getWorldManager()->unloadWorld($world, true);
            }
        }

        $sender->sendMessage(Util::PREFIX . "Vous venez de décharger §6" . $count . " §fbox(s) vide(s)");
    }

    protected function prepare(): void
    {
    }
}