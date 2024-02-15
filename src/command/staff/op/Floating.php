<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\entity\BlackSmith;
use Kitmap\entity\DefaultFloatingText;
use Kitmap\entity\ElevatorPhantom;
use Kitmap\handler\Cache;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\entity\Location;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;

class Floating extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "floating",
            "Fait disparaitre ou apparaitre les floatings texts"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        switch ($args["opt"]) {
            case "spawn":
                foreach (Cache::$config["floatings"] as $key => $value) {
                    list ($x, $y, $z, $world) = explode(":", $key);

                    $entity = new DefaultFloatingText(new Location(floatval($x), floatval($y), floatval($z), Main::getInstance()->getServer()->getWorldManager()->getWorldByName($world), 0, 0));
                    $entity->spawnToAll();
                }

                foreach (Cache::$config["elevators"] as $elevator) {
                    list($x, $y, $z, $yaw) = explode(":", $elevator);

                    $entity = new ElevatorPhantom(new Location(floatval($x), floatval($y), floatval($z), Main::getInstance()->getServer()->getWorldManager()->getWorldByName("mine"), intval($yaw), 0));
                    $entity->spawnToAll();
                }

                list($x, $y, $z, $yaw) = explode(":", Cache::$data["forgeron-position"] ?? Cache::$config["forgeron-positions"][0]);

                $entity = new BlackSmith(new Location(floatval($x), floatval($y), floatval($z), Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld(), intval($yaw), 0));
                $entity->spawnToAll();

                $sender->sendMessage(Util::PREFIX . "Vous venez de faire apparaitre les floatings texts");
                break;
            case "despawn":
                foreach (Main::getInstance()->getServer()->getWorldManager()->getWorlds() as $world) {
                    foreach ($world->getEntities() as $entity) {
                        if ($entity instanceof DefaultFloatingText || $entity instanceof ElevatorPhantom || $entity instanceof BlackSmith) {
                            $entity->flagForDespawn();
                        }
                    }
                }

                $sender->sendMessage(Util::PREFIX . "Vous venez de supprimer les floatings texts");
                break;
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("opt", ["spawn", "despawn"]));
    }
}