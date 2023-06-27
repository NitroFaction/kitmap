<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\event;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\entity\Location;
use pocketmine\plugin\PluginBase;
use skymin\bossbar\BossBarAPI;
use Util\entity\entities\Boss as BossEntity;

class Boss extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "boss",
            "Fait spawn un boss ou despawn"
        );

        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        switch ($args["opt"]) {
            case "spawn":
                $position = explode(":", Cache::$config["boss_spawn"]);
                $position = new Location(intval($position[0]), intval($position[1]), intval($position[2]), Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld(), 0, 0);

                $entity = new BossEntity($position);
                $entity->spawnToAll();

                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Un boss vient de spawn devant la sortie principal du spawn ! Allez le tuer §e/event boss §f!");
                break;
            case "despawn":
                foreach (Main::getInstance()->getServer()->getOnlinePlayers() as $player) {
                    BossBarAPI::getInstance()->hideBossBar($player, 0);
                }

                foreach (Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getEntities() as $entity) {
                    if ($entity instanceof BossEntity) {
                        $entity->flagForDespawn();
                    }
                }

                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Tous les boss ont disparus !");
                break;
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("opt", ["spawn", "despawn"]));
    }
}