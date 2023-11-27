<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Rank;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\entity\object\ExperienceOrb;
use pocketmine\entity\object\ItemEntity;
use pocketmine\plugin\PluginBase;

class Clearlagg extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "clearlagg",
            "Effectue un clearlagg forcé"
        );

        $this->setPermissions([Rank::GROUP_STAFF]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $count = 0;

        foreach (Main::getInstance()->getServer()->getWorldManager()->getWorlds() as $world) {
            foreach ($world->getEntities() as $entity) {
                if ($entity instanceof ItemEntity || $entity instanceof ExperienceOrb) {
                    if ($entity instanceof ItemEntity) {
                        $count++;
                    }

                    $entity->flagForDespawn();
                }
            }
        }

        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§q" . $count . " §fentitée(s) ont été supprimée(s) lors d'un nettoyage forcé");
    }

    protected function prepare(): void
    {
    }
}