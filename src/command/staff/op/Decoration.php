<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\args\FloatArgument;
use CortexPE\Commando\BaseCommand;
use Element\entity\decoration\ChristmasEntity;
use Element\util\args\OptionArgument;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Decoration extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "decoration",
            "Fait spawn une decoration à l'endroit ou vous êtes"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $size = $args["taille"] ?? 1;
            $entity = ChristmasEntity::$entities[$args["decoration"]];

            $entity = new $entity($sender->getLocation());
            $entity->spawnToAll();
            $entity->setScale($size);
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("decoration", array_keys(ChristmasEntity::$entities)));
        $this->registerArgument(1, new FloatArgument("taille", true));
    }
}