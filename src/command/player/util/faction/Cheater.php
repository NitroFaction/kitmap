<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\faction;

use CortexPE\Commando\BaseCommand;
use NCore\Base;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Cheater extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "cheater",
            "Alerte au staffs si un cheateur est sur vous"
        );

        $this->setAliases(["cheat", "cheateur"]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $sender->sendMessage(Util::PREFIX . "Tous les staffs connectés viennent de recevoir votre demande d'aide !");

            foreach (Base::getInstance()->getServer()->getOnlinePlayers() as $target) {
                if ($target->hasPermission("staff.group")) {
                    $target->sendMessage(str_repeat(Util::PREFIX . "Le joueur §e" . $sender->getName() . " §fsignale un cheateur sur lui!\n", 2));
                }
            }
        }
    }

    protected function prepare(): void
    {
    }
}