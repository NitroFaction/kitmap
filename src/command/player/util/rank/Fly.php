<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\rank;

use CortexPE\Commando\BaseCommand;
use NCore\handler\RankAPI;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Fly extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "fly",
            "Permet de voler sur les îles de faction"
        );
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            if (!RankAPI::hasRank($sender, "champion")) {
                $sender->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de faire cela");
                return;
            }

            if (!$sender->getAllowFlight()) {
                if ($sender->getGamemode() === GameMode::SURVIVAL()) {
                    if (!str_starts_with($sender->getWorld()->getFolderName(), "island-")) {
                        $sender->sendMessage(Util::PREFIX . "Vous ne pouvez voler que dans les îles de faction");
                        return;
                    }

                    $sender->setAllowFlight(true);
                    $sender->sendMessage(Util::PREFIX . "Vous pouvez désormais voler");
                } else {
                    $sender->sendMessage(Util::PREFIX . "Vous ne pouvez activer le fly mod seulement en survie");
                }
            } else {
                $sender->setAllowFlight(false);
                $sender->setFlying(false);
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez désormais plus voler");
            }
        }
    }

    protected function prepare(): void
    {
    }
}