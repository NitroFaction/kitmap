<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player\rank;

use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\Rank;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class ResetStats extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "resetstats",
            "Réinitialise ses statistiques"
        );

        $this->setAliases(["rs"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if (!Rank::hasRank($sender, "champion")) {
                $sender->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de faire cela");
                return;
            }

            $session->data["kill"] = 0;
            $session->data["death"] = 0;
            $session->data["killstreak"] = 0;

            $username = strtolower($sender->getName());

            Cache::$players["kill"][$username] = 0;
            Cache::$players["death"][$username] = 0;
            Cache::$players["killstreak"][$username] = 0;

            $sender->sendMessage(Util::PREFIX . "Vous venez de réinitialiser vos statistiques");
        }
    }

    protected function prepare(): void
    {
    }
}