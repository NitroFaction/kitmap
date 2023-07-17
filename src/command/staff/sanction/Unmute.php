<?php /** @noinspection PhpUnused */

namespace NCore\command\staff\sanction;

use CortexPE\Commando\args\TargetArgument;
use CortexPE\Commando\BaseCommand;
use NCore\Base;
use NCore\handler\Cache;
use NCore\handler\discord\Discord;
use NCore\handler\discord\EmbedBuilder;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Unmute extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "unmute",
            "Redonne la parole à un joueur"
        );

        $this->setPermission("staff.group");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $target = Base::getInstance()->getServer()->getPlayerByPrefix($args["joueur"]);

        if (!$target instanceof Player) {
            $sender->sendMessage(Util::PREFIX . "Le joueur indiqué n'est pas connecté sur le serveur");
            return;
        }
        $session = Session::get($target);

        if (!$session->inCooldown("mute")) {
            $sender->sendMessage(Util::PREFIX . "Le joueur §e" . $target->getName() . " §fn'est pas mute");
            return;
        }

        $session->removeCooldown("mute");

        $sender->sendMessage(Util::PREFIX . "Vous venez de unmute §e" . $target->getName());
        $target->sendMessage(Util::PREFIX . "Vous venez d'être unmute par §e" . $sender->getName());

        $embed = new EmbedBuilder();
        $embed->setDescription("**Unmute**\n\n**Joueur**\n" . $target->getName() . "\n\n*Unmute par le staff: " . $sender->getName() . "*");
        $embed->setColor(5635925);
        Discord::send($embed, Cache::$config["sanction_webhook"]);
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetArgument("joueur"));
    }
}