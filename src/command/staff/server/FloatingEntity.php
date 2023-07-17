<?php /** @noinspection PhpUnused */

namespace NCore\command\staff\server;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use NCore\Base;
use NCore\entity\entities\BetEntity;
use NCore\entity\entities\floating\BaseFloating;
use NCore\entity\entities\floating\FloatingTextEntity;
use NCore\entity\entities\floating\LeaderboardsFloating;
use NCore\entity\entities\ForgeronEntity;
use NCore\entity\entities\MinerEntity;
use NCore\entity\entities\TopEntity;
use NCore\handler\Cache;
use NCore\handler\SkinAPI;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\entity\Location;
use pocketmine\entity\Villager;
use pocketmine\item\ItemFactory;
use pocketmine\plugin\PluginBase;
use Util\util\IdsUtils;

class FloatingEntity extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "floating",
            "Fait disparaitre ou apparaitre les floatings texts"
        );

        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        switch ($args["opt"]) {
            case "spawn":
                $defaultWorld = Base::getInstance()->getServer()->getWorldManager()->getDefaultWorld();

                foreach (Cache::$config["floatings"] as $key => $value) {
                    list ($x, $y, $z, $world) = explode(":", $key);

                    $entity = new BaseFloating(new Location(floatval($x), floatval($y), floatval($z), Base::getInstance()->getServer()->getWorldManager()->getWorldByName($world), 0, 0));
                    $entity->spawnToAll();
                }

                foreach (Cache::$config["top"] as $key => $value) {
                    list ($x, $y, $z, $world) = explode(":", $key);

                    $entity = new TopEntity(new Location(floatval($x), floatval($y), floatval($z), Base::getInstance()->getServer()->getWorldManager()->getWorldByName($world), 0, 0), SkinAPI::getSkinFromName("steve"));
                    $entity->spawnToAll();
                }

                $entity = new LeaderboardsFloating(new Location(0.5, 65.5, -11.5, $defaultWorld, 0, 0));
                $entity->spawnToAll();

                $entity = new BetEntity(new Location(-6.5, 64, 27.5, $defaultWorld, 180, 0), SkinAPI::getSkinFromName("bet"));
                $entity->spawnToAll();

                $entity = new MinerEntity(new Location(170.5, 29, 199.5, Base::getInstance()->getServer()->getWorldManager()->getWorldByName("farm"), 270, 0), SkinAPI::getSkinFromName("miner"));
                $entity->getInventory()->setItemInHand(ItemFactory::getInstance()->get(IdsUtils::ILVAITE_PICKAXE));
                $entity->spawnToAll();

                list ($x, $y, $z, $yaw) = explode(":", Cache::$dynamic["forgeron"]);

                $entity = new ForgeronEntity(new Location(floatval($x), intval($y), floatval($z), $defaultWorld, intval($yaw), 0));
                $entity->setProfession(Villager::PROFESSION_BLACKSMITH);
                $entity->spawnToAll();

                $sender->sendMessage(Util::PREFIX . "Vous venez de faire apparaitre les floatings texts");
                break;
            case "despawn":
                foreach (Base::getInstance()->getServer()->getWorldManager()->getWorlds() as $world) {
                    foreach ($world->getEntities() as $entity) {
                        if ($entity instanceof FloatingTextEntity || $entity instanceof MinerEntity || $entity instanceof ForgeronEntity || $entity instanceof TopEntity || $entity instanceof BetEntity) {
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