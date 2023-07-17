<?php /** @noinspection PhpUnused */

namespace NCore\command\staff;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseCommand;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use Util\entity\entities\Boss;
use Util\entity\entities\EmeraldGolem;
use Util\entity\entities\Goblin;
use Util\entity\entities\VillagerFarmer;

class Summon extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "summon",
            "Fait spawn n'importe quel entité"
        );

        $this->setPermission("staff.group");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $name = $args["opt"];

            if (!in_array($name, ["goblin", "boss", "golem", "villager"])) {
                return;
            }

            $class = match ($name) {
                "goblin" => Goblin::class,
                "boss" => Boss::class,
                "golem" => EmeraldGolem::class,
                "villager" => VillagerFarmer::class
            };

            $entity = new $class($sender->getLocation());
            $entity->spawnToAll();
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("opt"));
    }
}