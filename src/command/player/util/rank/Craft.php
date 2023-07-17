<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\rank;

use CortexPE\Commando\BaseCommand;
use NCore\handler\RankAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use Util\world\block\CraftingTable;

class Craft extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "craft",
            "Ouvre un établi n'importe où"
        );
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->data["player"]["staff_mod"][0] || $sender->getGamemode() === GameMode::SPECTATOR()) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas accèder à votre enderchest en étant en staff mod");
                return;
            } else if (!RankAPI::hasRank($sender, "prince")) {
                $sender->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de faire cela");
                return;
            }

            CraftingTable::WORKBENCH()->send($sender);
        }
    }

    protected function prepare(): void
    {
    }
}