<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\money;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TargetArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
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

        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $data = $args["valeur"];
        $amount = intval($args["montant"]);

        if ($args["joueur"] === "@a") {
            Util::allSelectorExecute($sender, $this->getName(), $args);
            return;
        }

        if (($target = Main::getInstance()->getServer()->getPlayerByPrefix($args["joueur"])) instanceof Player) {
            $target = $target->getName();
        } else {
            $target = strtolower($args["joueur"]);

            if (!isset(Cache::$players["upper_name"][$target])) {
                $sender->sendMessage(Util::PREFIX . "Ce joueur ne s'est jamais connecté au serveur (verifiez bien les caractères)");
                return;
            }
        }

        if (0 > $amount) {
            $sender->sendMessage(Util::PREFIX . "Le montant que vous avez inscrit est invalide");
            return;
        }

        $sender->sendMessage(Util::PREFIX . "Vous venez d'ajouter §e" . $amount . " §f" . $data . " au joueur §e" . $target);
        Addvalue::addValue($sender->getName(), $target, $data, $amount);
    }

    public static function addValue(string $staff, string $key, string $column, int $value): void
    {
        $player = Main::getInstance()->getServer()->getPlayerByPrefix($key);

        if ($player instanceof Player) {
            Session::get($player)->addValue($column, $value);

            if ($value > 0) {
                $player->sendMessage(Util::PREFIX . "Le staff §e" . $staff . " §fvient de vous ajouter §e" . $value . " §f" . $column);
            } else {
                $player->sendMessage(Util::PREFIX . "Le staff §e" . $staff . " §fvient de vous retirer §e" . $value . " §f" . $column);
            }
        } else {
            $file = Util::getFile("players/" . $key);

            if ($file->getAll() !== []) {
                $file->set($column, $file->get($column) + $value);
                $file->save();
            }
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetArgument("joueur"));
        $this->registerArgument(0, new RawStringArgument("joueur"));
        $this->registerArgument(1, new IntegerArgument("montant"));
        $this->registerArgument(2, new OptionArgument("valeur", ["money", "pack", "kill", "gem", "killstreak", "death"]));
    }
}