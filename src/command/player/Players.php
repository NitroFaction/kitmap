<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player;

use CortexPE\Commando\BaseCommand;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use Symfony\Component\Filesystem\Path;

class Players extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "list",
            "Récupére la liste des joueurs connectés au serveur"
        );

        $this->setAliases(["players"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $players = Main::getInstance()->getServer()->getOnlinePlayers();

        $players = array_map(function (Player $player): string {
            return $player->getName();
        }, $players);

        $path = Path::join(Main::getInstance()->getServer()->getDataPath(), "players");
        $count = count(glob($path . "/*")) + 1;

        $list = implode("§f, §6", $players);
        $sender->sendMessage(Util::PREFIX . "Voici la liste des joueurs connectés sur le serveur actuellement (§6" . count($players) . "§f)\n§6" . $list . "\n§6" . $count . " §fjoueurs uniques se sont déjà connectés au serveur");
    }

    protected function prepare(): void
    {
    }
}