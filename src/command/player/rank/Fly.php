<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player\rank;

use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Rank;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
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
            "Permet de voler sur les iles de faction"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            if (!Rank::hasRank($sender, "champion")) {
                $sender->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de faire cela");
                return;
            }

            if (!$sender->getAllowFlight()) {
                if ($sender->getGamemode() === GameMode::SURVIVAL()) {
                    if (!str_starts_with($sender->getWorld()->getFolderName(), "island-")) {
                        $sender->sendMessage(Util::PREFIX . "Vous ne pouvez voler que dans les iles de faction");
                        return;
                    }

                    $sender->setAllowFlight(true);
                    $sender->sendMessage(Util::PREFIX . "Vous pouvez désormais voler");
                } else {
                    $sender->sendMessage(Util::PREFIX . "Vous ne pouvez activer le fly seulement en survie");
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