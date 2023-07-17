<?php /** @noinspection PhpUnused */

namespace NCore\command\staff\tool;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\BaseCommand;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Near extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "near",
            "Trouve tous les joueurs dans un rayon précis (15 par défaut)"
        );

        $this->setPermission("staff.group");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $distance = $args["distance"] ?? 16;

            if (0 >= $distance) {
                $sender->sendMessage(Util::PREFIX . "La distance indiqué est invalide");
                return;
            }

            $players = $this->getNearestPlayers($sender, $distance);

            if (count($players) === 0) {
                $sender->sendMessage(Util::PREFIX . "Il n'y a personne dans le rayon de §e" . $distance . " §fautour de vous");
                return;
            }

            $sender->sendMessage(Util::PREFIX . "Il y a §e" . count($players) . " §fjoueurs dans le rayon de §e" . $distance . " §fatour de vous: §e" . implode("§f, §e", $players));
        }
    }

    private function getNearestPlayers(Player $player, int $distance): array
    {
        $result = [];

        foreach ($player->getWorld()->getPlayers() as $target) {
            if ($player !== $target && $distance >= $target->getPosition()->distance($player->getPosition())) {
                if (Session::get($target)->data["player"]["staff_mod"][0]) {
                    $result[] = $target->getName() . " (§fStaffMode§e)";
                } else if ($target->getDisplayName() !== $target->getName()) {
                    $result[] = $target->getDisplayName() . " (§fNick de: " . $target->getName() . "§e)";
                } else {
                    $result[] = $target->getName();
                }
            }
        }
        return $result;
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new IntegerArgument("distance", true));
    }
}