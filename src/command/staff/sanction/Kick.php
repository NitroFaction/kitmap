<?php /** @noinspection PhpUnused */

namespace NCore\command\staff\sanction;

use CortexPE\Commando\args\TargetArgument;
use CortexPE\Commando\BaseCommand;
use NCore\handler\SanctionAPI;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Kick extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "kick",
            "Expulse un joueur du serveur"
        );

        $this->setPermission("staff.group");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            SanctionAPI::sanctionForm($sender, $args["joueur"], "kick");
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetArgument("joueur"));
    }
}