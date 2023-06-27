<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\tool;

use CortexPE\Commando\BaseCommand;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class RandomTp extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "randomtp",
            "Se téléporte à un joueur au hasard connecté"
        );

        $this->setPermission("staff.group");
        $this->setAliases(["rtp"]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $players = Main::getInstance()->getServer()->getOnlinePlayers();
        $target = $players[array_rand($players)];

        if ($sender instanceof Player) {
            if (in_array($sender->getName(), Vanish::$vanish)) {
                foreach (Main::getInstance()->getServer()->getOnlinePlayers() as $player) {
                    if ($player->hasPermission("staff.group") || $player->getName() === $sender->getName()) {
                        continue;
                    }
                    $player->hidePlayer($sender);
                }
            } elseif (count($players) === 1) {
                $sender->sendMessage(Util::PREFIX . "Vous êtes seul sur le serveur");
                return;
            }

            $sender->teleport($target->getPosition());
            $sender->sendMessage(Util::PREFIX . "Vous avez été téléporté sur le joueur §e" . $target->getName());
        }
    }

    protected function prepare(): void
    {
    }
}