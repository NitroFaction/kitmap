<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player\rank;

use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Rank;
use Kitmap\Session;
use Kitmap\Util;
use muqsit\invmenu\InvMenu;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Craft extends BaseCommand
{
    public const INV_MENU_TYPE_WORKBENCH = "nitro:workbench";

    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "craft",
            "Ouvre un établi n'importe où"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->data["staff_mod"][0] || $sender->getGamemode() === GameMode::SPECTATOR()) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas accèder à votre enderchest en étant en staff mod");
                return;
            } else if (!Rank::hasRank($sender, "prince")) {
                $sender->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de faire cela");
                return;
            }

            InvMenu::create(self::INV_MENU_TYPE_WORKBENCH)->send($sender);
        }
    }

    protected function prepare(): void
    {
    }
}