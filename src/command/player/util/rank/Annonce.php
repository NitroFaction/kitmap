<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\rank;

use CortexPE\Commando\args\TextArgument;
use CortexPE\Commando\BaseCommand;
use NCore\Base;
use NCore\handler\RankAPI;
use NCore\handler\SanctionAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Annonce extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "annonce",
            "Fait passer une annonce au serveur"
        );
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if (!RankAPI::hasRank($sender, "elite")) {
                $sender->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de faire cela");
                return;
            } else if ($session->inCooldown("mute")) {
                $sender->sendMessage(Util::PREFIX . "Vous êtes mute, temps restant: §e" . SanctionAPI::format($session->getCooldownData("mute")[0] - time()));
                return;
            }

            if ($session->inCooldown("annonce")) {
                $seconds = $session->getCooldownData("annonce")[0] - time();

                $_seconds = $seconds % 60;
                $minutes = floor(($seconds % 3600) / 60);

                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez actuellement pas faire d'annonce, merci d'attendre §e" . $minutes . " §fminutes et §e" . $_seconds . " §fsecondes !");
            } else {
                $session->setCooldown("annonce", round((60 * 120)));
                Base::getInstance()->getServer()->broadcastMessage("§e§lANNONCE§r §f" . $sender->getName() . " " . Util::PREFIX . implode(" ", $args));
            }
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TextArgument("message"));
    }
}