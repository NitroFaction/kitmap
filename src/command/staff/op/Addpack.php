<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;

class Addpack extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "addpack",
            "Ajoute des packs à un ou des joueurs"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $data = self::getUpperPackName($args["valeur"]);
        $amount = intval($args["montant"]);

        $player = Addvalue::addValue($sender, $this->getName(), $args);

        if (is_null($player)) {
            return;
        }

        $sender->sendMessage(Util::PREFIX . "Vous venez d'ajouter §9" . $amount . " §fpacks §9" . $data . " §fau joueur §9" . $player);
        Util::addValue($sender->getName(), $player, ["packs", $data], $amount);
    }

    public static function getUpperPackName(string $data): string
    {
        $names = array_keys(Cache::$config["default-data"]["packs"]);

        foreach ($names as $name) {
            if (strtolower($name) === strtolower($data)) {
                return $name;
            }
        }
        return $names[0];
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(false, "joueur"));
        $this->registerArgument(0, new RawStringArgument("joueur"));
        $this->registerArgument(1, new IntegerArgument("montant"));
        $this->registerArgument(2, new OptionArgument("valeur", array_keys(array_change_key_case(Cache::$config["default-data"]["packs"]))));
    }
}