<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\faction;

use CortexPE\Commando\BaseCommand;
use NCore\Base;
use NCore\handler\Cache;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\world\Position;

class ClaimLibre extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "claimlibre",
            "Trouve le claim le plus proche de votre position et ses coordonnées"
        );
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $distance = null;
            $plot = null;

            if ($sender->getWorld() !== Base::getInstance()->getServer()->getWorldManager()->getDefaultWorld()) {
                $sender->sendMessage(Util::PREFIX . "Il faut être dans le monde ou il y a les claims pour executer cette commande");
                return;
            }

            foreach (Cache::$plots as $id => $data) {
                if (!is_null($data["faction"])) {
                    continue;
                }

                $pos = new Position($data["min_x"], $sender->getPosition()->getY(), $data["min_z"], $sender->getWorld());
                $dist = $sender->getPosition()->distance($pos);

                if (is_null($distance) || $distance > $dist) {
                    $distance = $dist;
                    $plot = $id;
                }
            }

            if (is_null($plot)) {
                $sender->sendMessage(Util::PREFIX . "Il n'y a aucun claim libre");
                return;
            }

            $sender->sendMessage(Util::PREFIX . "Le claim le plus proche est le §e" . $plot . " §favec les coordonnées §eX: " . Cache::$plots[$plot]["min_x"] . "§f, Z: §e" . Cache::$plots[$plot]["min_z"]);
        }
    }

    protected function prepare(): void
    {
    }
}