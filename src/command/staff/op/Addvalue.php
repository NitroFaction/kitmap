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

class Addvalue extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "addvalue",
            "Ajoute n'importe quel valeur dans les data d'un joueur"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $data = $args["valeur"];
        $amount = intval($args["montant"]);

        $player = self::addValue($sender, $this->getName(), $args);

        if (is_null($player)) {
            return;
        }

        $sender->sendMessage(Util::PREFIX . "Vous venez d'ajouter §9" . $amount . " §f" . $data . " au joueur §9" . $player);
        Util::addValue($sender->getName(), $player, $data, $amount);
    }

    public static function addValue(CommandSender $sender, string $commandName, array $args): ?string
    {
        $amount = intval($args["montant"]);

        if ($args["joueur"] === "@a") {
            Util::allSelectorExecute($sender, $commandName, $args);
            return null;
        }

        $player = Util::findPlayerByName($args["joueur"]);

        if (is_null($player)) {
            $sender->sendMessage(Util::PREFIX . "Ce joueur ne s'est jamais connecté au serveur (verifiez bien les caractères)");
            return null;
        } else if (0 > $amount) {
            $sender->sendMessage(Util::PREFIX . "Le montant que vous avez inscrit est invalide");
            return null;
        }

        return $player;
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(false, "joueur"));
        $this->registerArgument(0, new RawStringArgument("joueur"));
        $this->registerArgument(1, new IntegerArgument("montant"));
        $this->registerArgument(2, new OptionArgument("valeur", array_keys(array_filter(Cache::$config["default-data"], "is_int"))));
    }
}