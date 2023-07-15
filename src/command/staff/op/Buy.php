<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TargetArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;

class Buy extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "buy",
            "Ajoute un grade à un joueur ou des gemmes avec un message"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (isset($args["gemme"])) {
            Util::executeCommand("addvalue \"" . $args["joueur"] . "\" " . $args["gemme"] . " gem");
            Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§eLe joueur §f" . $args["joueur"] . " §evient d'acheter §f" . $args["gemme"] . " §egemmes sur la boutique !! §fhttps://shop.nitrofaction.fr");
        } else if (isset($args["grade"])) {
            Util::executeCommand("setrank \"" . $args["joueur"] . "\" " . $args["grade"]);
            Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§eLe joueur §f" . $args["joueur"] . " §evient d'acheter le grade §f" . $args["grade"] . " §esur la boutique !! §fhttps://shop.nitrofaction.fr");
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetArgument("joueur"));
        $this->registerArgument(0, new RawStringArgument("joueur"));
        $this->registerArgument(1, new IntegerArgument("gemme"));
        $this->registerArgument(1, new OptionArgument("grade", array_keys(Cache::$config["ranks"])));
    }
}