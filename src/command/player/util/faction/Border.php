<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\faction;

use CortexPE\Commando\BaseCommand;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
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
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->data["player"]["border"]) {
                $session->data["player"]["border"] = false;
                $sender->sendMessage(Util::PREFIX . "Vous ne verrez plus la limite du chunk ou vous vous trouvez");
            } else {
                $session->data["player"]["border"] = true;
                $sender->sendMessage(Util::PREFIX . "Vous voyez desormais la limite du chunk ou vous vous trouvez");
            }
        }
    }

    protected function prepare(): void
    {
    }
}