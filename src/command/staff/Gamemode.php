<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff;

use CortexPE\Commando\BaseCommand;
use Element\util\args\OptionArgument;
use Element\util\args\TargetArgument;
use Kitmap\handler\Rank;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\lang\Language;
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\player\GameMode as PmGameMode;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;

class Gamemode extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "gamemode",
            "Changer son mode de jeu/celui d'un joueur"
        );

        $this->setPermissions([Rank::GROUP_STAFF]);
        $this->setAliases(["gm"]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            if (isset($args["mode"])) {
                $gameMode = match (strtolower($args["mode"])) {
                    "0", "survie" => PmGameMode::SURVIVAL(),
                    "1", "creatif" => PmGameMode::CREATIVE(),
                    "2", "aventure" => PmGameMode::ADVENTURE(),
                    "3", "spectateur" => PmGameMode::SPECTATOR(),
                    default => null
                };

                if (is_null($gameMode)) {
                    $sender->sendMessage(Util::PREFIX . "Le mode de jeu que vous avez spécifié est invalide");
                    return;
                }
            } else {
                $sender->sendMessage(Util::PREFIX . "Vous devez obligatoirement spécifié un mode de jeu");
                return;
            }

            $gameModeName = str_replace("Mode ", "", (new Language("fra"))->translate($gameMode->getTranslatableName()));

            if (isset($args["joueur"])) {
                /* @noinspection PhpDeprecationInspection */
                $target = Server::getInstance()->getPlayerByPrefix($args["joueur"]);

                if (!$target instanceof Player) {
                    $sender->sendMessage(Util::PREFIX . "Le joueur indiqué n'est pas connecté sur le serveur");
                    return;
                } else if (!$sender->hasPermission(DefaultPermissionNames::COMMAND_GAMEMODE_OTHER)) {
                    $sender->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de modifier le mode de jeu d'un autre joueur que vous-même");
                    return;
                }

                $target->setGamemode($gameMode);
                $sender->sendMessage(Util::PREFIX . "Vous venez de changer le mode de jeu de §q" . $target->getName() . " §fpour §q" . $gameModeName . " §f!");
            } else {
                $sender->setGamemode($gameMode);
                $sender->sendMessage(Util::PREFIX . "Vous venez de définir votre mode de jeu à §q" . $gameModeName . " §f!");
            }
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("mode", ["0", "1", "2", "3", "survie", "creatif", "aventure", "spectateur"]));
        $this->registerArgument(1, new TargetArgument("joueur", true));
    }
}