<?php /** @noinspection PhpUnused */

namespace NCore\command\staff\tool;

use CortexPE\Commando\BaseCommand;
use NCore\Base;
use NCore\entity\entities\ForgeronEntity;
use NCore\handler\Cache;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\entity\Location;
use pocketmine\entity\Villager;
use pocketmine\plugin\PluginBase;

class MoveForgeron extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "moveforgeron",
            "Change l'emplacement du forgeron"
        );

        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $config = Cache::$config["forgeron"];
        $xyzy = $config[array_rand($config)];

        Cache::$dynamic["forgeron"] = $xyzy;
        list ($x, $y, $z, $yaw) = explode(":", $xyzy);

        foreach (Base::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getEntities() as $entity) {
            if ($entity instanceof ForgeronEntity) {
                $entity->flagForDespawn();
            }
        }

        $entity = new ForgeronEntity(new Location(floatval($x), intval($y), floatval($z), Base::getInstance()->getServer()->getWorldManager()->getDefaultWorld(), intval($yaw), 0));
        $entity->setProfession(Villager::PROFESSION_BLACKSMITH);
        $entity->spawnToAll();

        $sender->sendMessage(Util::PREFIX . "Vous venez de changer l'emplacement du forgeron");
    }

    protected function prepare(): void
    {
    }
}