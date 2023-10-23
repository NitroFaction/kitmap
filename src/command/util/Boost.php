<?php /** @noinspection PhpUnused */

namespace Kitmap\command\util;

use CortexPE\Commando\BaseCommand;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
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

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);
            $booster = $session->data["boost"] ?? [0, false];

            if (time() > ($booster[0] + (60 * 60 * 24))) {
                $sender->sendMessage(Util::PREFIX . "Vous n'êtes pas booster ou les §e24h§f pour récupérer vos récompenses ont §eéxpirés§f, vous pouvez refaire la commande §e/claim-boost §fsur le discord si vous êtes toujours booster !");
                return;
            } else if ($booster[1]) {
                $sender->sendMessage(Util::PREFIX . "Vous avez déjà récupéré vos récompenses de booster aujourd'hui ! Revenez quad vous aurez de nouveau fait la commande §e/claim-boost §fsur le discord");
                return;
            }

            $session->addValue("pack", 2);
            $session->data["boost"][1] = true;

            Util::executeCommand("givekit \"" . $sender->getName() . "\" prince");
            Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le joueur §e" . $sender->getName() . " §fvient de recevoir §e2 §fpack(s) et un §ekit prince §fcar il a boosté le serveur discord !");
        }
    }

    protected function prepare(): void
    {
    }
}