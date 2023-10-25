<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Rank;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;

class Findnick extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "findnick",
            "Trouve le joueur possedant le nick de son choix"
        );

        $this->setPermissions([Rank::GROUP_STAFF]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $nick = $args["pseudo"];
        $found = null;

        foreach (Main::getInstance()->getServer()->getOnlinePlayers() as $player) {
            if (strtolower($player->getDisplayName()) === strtolower($nick)) {
                if ($player->getName() !== $player->getDisplayName()) {
                    $found = $player->getName();
                    break;
                }
            }
        }

        if (is_null($found)) {
            $sender->sendMessage(Util::PREFIX . "Aucun joueur ne possede ce nick actuellement connecté sur le serveur");
        } else {
            $sender->sendMessage(Util::PREFIX . "Le pseudo §6" . $nick . " §fest le nick du joueur §6" . $found);
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("pseudo"));
    }
}