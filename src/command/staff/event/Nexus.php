<?php /** @noinspection PhpUnused */

namespace NCore\command\staff\event;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use NCore\Base;
use NCore\entity\entities\NexusEntity;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\entity\Location;
use pocketmine\plugin\PluginBase;
use skymin\bossbar\BossBarAPI;

class Nexus extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "nexus",
            "Commence ou arrête un event nexus !"
        );

        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $entities = [];

        foreach (Base::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getEntities() as $entity) {
            if ($entity instanceof NexusEntity) {
                $entities[] = $entity;
            }
        }

        switch ($args["opt"]) {
            case "start":
                if (count($entities) !== 0) {
                    $sender->sendMessage(Util::PREFIX . "Un event nexus est déjà en cours... Vous pouvez l'arrêter avec la commande §e/nexus end");
                    return;
                }

                $nexus = new NexusEntity(new Location(-47.5, 59, 146.5, Base::getInstance()->getServer()->getWorldManager()->getDefaultWorld(), 0, 0));
                $nexus->spawnToAll();

                Base::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Un event nexus vient de commencer ! Vous pouvez vous y téléporter grace à la commande §e/event nexus");
                break;
            case "end":
                Base::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "L'event nexus a été arrêté, pas de stuff :/");

                foreach (Base::getInstance()->getServer()->getOnlinePlayers() as $player) {
                    BossBarAPI::getInstance()->hideBossBar($player, 2);
                }

                foreach ($entities as $entity) {
                    $entity->flagForDespawn();
                }
                break;
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("opt", ["start", "end"]));
    }
}