<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\entity\Nexus as NexusEntity;
use Kitmap\handler\Cache;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\entity\Location;
use pocketmine\permission\DefaultPermissions;
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

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $entities = [];

        foreach (Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getEntities() as $entity) {
            if ($entity instanceof NexusEntity) {
                $entities[] = $entity;
            }
        }

        switch ($args["opt"]) {
            case "start":
                if (count($entities) !== 0) {
                    $sender->sendMessage(Util::PREFIX . "Un event nexus est déjà en cours... Vous pouvez l'arrêter avec la commande §9/nexus end");
                    return;
                }

                [$x, $y, $z] = explode(":", Cache::$config["nexus"]);

                $nexus = new NexusEntity(new Location(floatval($x), floatval($y), floatval($z), Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld(), 0, 0));
                $nexus->spawnToAll();

                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Un event nexus vient de commencer ! Vous pouvez vous y téléporter grace à la commande §9/event nexus");
                break;
            case "end":
                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "L'event nexus a été arrêté, pas de stuff :/");

                foreach (Main::getInstance()->getServer()->getOnlinePlayers() as $player) {
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