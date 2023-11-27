<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\BaseCommand;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;

class ResetBourse extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "resetbourse",
            "Réinitialise la bourse"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        Util::resetBourse();
        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "La bourse vient d'être réinitialisé ! Profitez bien !");
    }

    protected function prepare(): void
    {
    }
}