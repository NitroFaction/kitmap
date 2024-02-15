<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Xp extends BaseCommand
{

    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "xp",
            "Se donner des niveaux d'xp"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $xp = intval($args["niveaux"]) ?? 1;
            $sender->getXpManager()->addXpLevels($xp);
            $sender->sendMessage(Util::PREFIX . "Vous venez de vous ajouter §9" . $xp . " niveau(x)");
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new IntegerArgument("niveaux"));
    }

}
