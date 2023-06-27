<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\server;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TargetArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\Rank;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Setrank extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "setrank",
            "Ajoute un grade à un joueur"
        );

        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!Rank::existRank($args["grade"])) {
            $sender->sendMessage(Util::PREFIX . "Ce rang n'existe pas ou vous n'avez pas respecté les majuscules");
            return;
        }

        if (($target = Main::getInstance()->getServer()->getPlayerByPrefix($args["joueur"])) instanceof Player) {
            Session::get($target)->removeCooldown("kit");
            $target = $target->getName();
        } else {
            $target = strtolower($args["joueur"]);

            if (!isset(Cache::$players["upper_name"][$target])) {
                $sender->sendMessage(Util::PREFIX . "Ce joueur ne s'est jamais connecté au serveur (verifiez bien les caractères)");
                return;
            }
        }

        Rank::setRank($target, $args["grade"]);
        $sender->sendMessage(Util::PREFIX . "Vous venez d'ajouter le rang §e" . $args["grade"] . " §fà un joueur (§e" . $target . "§f)");
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetArgument("joueur"));
        $this->registerArgument(0, new RawStringArgument("joueur"));
        $this->registerArgument(1, new OptionArgument("grade", array_keys(Cache::$config["ranks"])));
    }
}