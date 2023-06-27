<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\tool;

use CortexPE\Commando\BaseCommand;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;

class StaffList extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "stafflist",
            "Donne la liste de staff connectés au serveur"
        );

        $this->setPermission("staff.group");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $players = Main::getInstance()->getServer()->getOnlinePlayers();
        $array = [];

        foreach ($players as $player) {
            if ($player->hasPermission("staff.group")) {
                $staffmod = Session::get($player)->data["staff_mod"][0];
                $array[] = $player->getName() . ($staffmod ? " (§fModeStaff§e)" : "");
            }
        }

        $list = implode("§f, §e", $array);
        $sender->sendMessage(Util::PREFIX . "Voici la liste des staffs connectés sur le serveur actuellement (§e" . count($array) . "§f)\n§e" . $list);
    }

    protected function prepare(): void
    {
    }
}