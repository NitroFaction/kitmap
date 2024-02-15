<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Removevalue extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "removevalue",
            "Supprime n'importe quel valeur dans les data d'un joueur"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $data = $args["valeur"];
        $amount = intval($args["montant"]);

        $player = Addvalue::addValue($sender, $this->getName(), $args);

        if (is_null($player)) {
            return;
        }

        $sender->sendMessage(Util::PREFIX . "Vous venez de retirer §9" . $amount . " §f" . $data . " au joueur §9" . $player);
        Util::addValue($sender->getName(), $player, $data, $amount, true);
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(false, "joueur"));
        $this->registerArgument(0, new RawStringArgument("joueur"));
        $this->registerArgument(1, new IntegerArgument("montant"));
        $this->registerArgument(2, new OptionArgument("valeur", array_keys(array_filter(Cache::$config["default-data"], "is_int"))));
    }
}