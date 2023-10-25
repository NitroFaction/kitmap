<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff;

use CortexPE\Commando\args\TargetArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\discord\Discord;
use Kitmap\handler\discord\EmbedBuilder;
use Kitmap\handler\Rank;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
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

        $this->setPermissions([Rank::GROUP_STAFF]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        /** @noinspection PhpDeprecationInspection */
        $target = Main::getInstance()->getServer()->getPlayerByPrefix($args["joueur"]);

        if (!$target instanceof Player) {
            $sender->sendMessage(Util::PREFIX . "Le joueur indiqué n'est pas connecté sur le serveur");
            return;
        }
        $session = Session::get($target);

        if (!$session->inCooldown("mute")) {
            $sender->sendMessage(Util::PREFIX . "Le joueur §6" . $target->getName() . " §fn'est pas mute");
            return;
        }

        $session->removeCooldown("mute");

        $sender->sendMessage(Util::PREFIX . "Vous venez de unmute §6" . $target->getName());
        $target->sendMessage(Util::PREFIX . "Vous venez d'être unmute par §6" . $sender->getName());

        $embed = new EmbedBuilder();
        $embed->setDescription("**Unmute**\n\n**Joueur**\n" . $target->getName() . "\n\n*Unmute par le staff: " . $sender->getName() . "*");
        $embed->setColor(5635925);
        Discord::send($embed, Cache::$config["sanction-webhook"]);
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetArgument("joueur"));
    }
}