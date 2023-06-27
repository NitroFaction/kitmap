<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player;

use CortexPE\Commando\BaseCommand;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Border extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "border",
            "Active des particules pour voir les limites des chunks"
        );

        $this->setAliases(["chunk"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->data["border"]) {
                $session->data["border"] = false;
                $sender->sendMessage(Util::PREFIX . "Vous ne verrez plus la limite du chunk ou vous vous trouvez");
            } else {
                $session->data["border"] = true;
                $sender->sendMessage(Util::PREFIX . "Vous voyez desormais la limite du chunk ou vous vous trouvez");
            }
        }
    }

    protected function prepare(): void
    {
    }
}