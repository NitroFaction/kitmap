<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\faction;

use CortexPE\Commando\BaseCommand;
use NCore\Base;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Boost extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "boost",
            "Récuperez vos récompenses de booster !"
        );
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);
            $booster = $session->data["player"]["boost"];

            if (time() > ($booster[0] + (60 * 60 * 24))) {
                $sender->sendMessage(Util::PREFIX . "Vous n'êtes pas booster ou les §e24h§f pour récupérer vos récompenses ont §eéxpirés§f, vous pouvez refaire la commande §e/claim-boost §fsur le discord si vous êtes toujours booster !");
                return;
            } else if ($booster[1]) {
                $sender->sendMessage(Util::PREFIX . "Vous avez déjà récupéré vos récompenses de booster aujourd'hui ! Revenez quad vous aurez de nouveau fait la commande §e/claim-boost §fsur le discord");
                return;
            }

            if (!in_array("wumpus", $session->data["player"]["cosmetics"])) {
                $session->data["player"]["cosmetics"][] = "wumpus";
            }

            $session->addValue("pack", 2);
            $session->data["player"]["boost"][1] = true;

            Util::executeCommand("givekit \"" . $sender->getName() . "\" prince");
            Base::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le joueur §e" . $sender->getName() . " §fvient de recevoir §e2 §fpack(s), un §ekit prince §fet le cosmétique §ewumpus §fcar il a boosté le serveur discord !");
        }
    }

    protected function prepare(): void
    {
    }
}