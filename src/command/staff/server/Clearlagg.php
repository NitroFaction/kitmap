<?php /** @noinspection PhpUnused */

namespace NCore\command\staff\server;

use CortexPE\Commando\BaseCommand;
use NCore\Base;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\entity\object\ExperienceOrb;
use pocketmine\entity\object\ItemEntity;
use pocketmine\plugin\PluginBase;
use pocketmine\world\World;

class Clearlagg extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "clearlagg",
            "Effectue un clearlagg forcé"
        );

        $this->setPermission("staff.group");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $count = 0;

        foreach (Base::getInstance()->getServer()->getWorldManager()->getWorlds() as $world) {
            foreach ($world->getEntities() as $entity) {
                if ($entity instanceof ItemEntity || $entity instanceof ExperienceOrb) {
                    if ($entity instanceof ItemEntity) {
                        $count++;
                    }

                    $entity->flagForDespawn();
                }
            }

            foreach ($world->getLoadedChunks() as $chunkHash => $chunk) {
                World::getXZ($chunkHash, $chunkX, $chunkZ);

                if (count($world->getChunkPlayers($chunkX, $chunkZ)) === 0) {
                    $world->unloadChunk($chunkX, $chunkZ);
                }
            }
        }

        Base::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§e" . $count . " §fentitée(s) ont été supprimée(s) lors d'un nettoyage forcé");
    }

    protected function prepare(): void
    {
    }
}