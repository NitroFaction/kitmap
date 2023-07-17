<?php /** @noinspection PhpUnused */

namespace NCore\command\staff\tool;

use CortexPE\Commando\BaseCommand;
use NCore\Base;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\world\World;

class UnloadIslands extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "unloadislands",
            "Décharge les iles vides"
        );

        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $count = 0;

        $worlds = array_filter(Base::getInstance()->getServer()->getWorldManager()->getWorlds(), function (World $world) {
            return $world->getFolderName() !== "map" && $world->getFolderName() !== "farm";
        });

        if (1 > count($worlds)) {
            $sender->sendMessage(Util::PREFIX . "Aucune île vide à décharger");
            return;
        }

        foreach ($worlds as $world) {
            if ($world instanceof World && 1 > count($world->getPlayers())) {
                $count++;
                Base::getInstance()->getServer()->getWorldManager()->unloadWorld($world, true);
            }
        }

        $sender->sendMessage(Util::PREFIX . "Vous venez de décharger §e" . $count . " §file(s) vide(s)");
    }

    protected function prepare(): void
    {
    }
}