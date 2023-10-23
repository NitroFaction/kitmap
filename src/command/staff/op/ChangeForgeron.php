<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\BaseCommand;
use Kitmap\entity\Forgeron;
use Kitmap\handler\Cache;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\entity\Location;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;

class ChangeForgeron extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "changeforgeron",
            "Change le forgeron"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $positions = Cache::$config["forgeron-positions"];
        $position = $positions[array_rand($positions)];

        Cache::$data["forgeron-position"] = $position;

        foreach (Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getEntities() as $entity) {
            if ($entity instanceof Forgeron) {
                $entity->flagForDespawn();
            }
        }

        list($x, $y, $z, $yaw) = explode(":", Cache::$data["forgeron-position"]);

        $entity = new Forgeron(new Location(floatval($x), floatval($y), floatval($z), Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld(), intval($yaw), 0));
        $entity->spawnToAll();

        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le forgeron a décidé de bougé ! Il est toujours au spawn, mais plus au meme endroit");
    }

    protected function prepare(): void
    {
    }
}