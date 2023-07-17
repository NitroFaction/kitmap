<?php /** @noinspection PhpUnused */

namespace NCore\command\player;

use CortexPE\Commando\args\TextArgument;
use CortexPE\Commando\BaseCommand;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Reply extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "reply",
            "Répond au dernier message reçu"
        );

        $this->setAliases(["r"]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);
            $reply = $session->data["player"]["reply"];

            if (is_null($reply)) {
                $sender->sendMessage(Util::PREFIX . "Vous n'avez aucun message en attente de réponse");
                return;
            }

            $session->removeCooldown("cmd");
            $sender->chat("/mp \"" . $reply . "\" " . $args["message"]);
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TextArgument("message"));
    }
}