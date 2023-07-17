<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\rank;

use CortexPE\Commando\BaseCommand;
use NCore\handler\RankAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
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
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if (!RankAPI::hasRank($sender, "champion")) {
                $sender->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de faire cela");
                return;
            }

            $session->data["player"]["kill"] = 0;
            $session->data["player"]["death"] = 0;
            $session->data["player"]["killstreak"] = 0;

            $sender->sendMessage(Util::PREFIX . "Vous venez de réinitialiser vos statistiques");
        }
    }

    protected function prepare(): void
    {
    }
}