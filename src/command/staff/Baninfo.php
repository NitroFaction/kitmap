<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\Rank;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;

class Baninfo extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "baninfo",
            "Affiche des informations sur le bannissement d'un joueur"
        );

        $this->setPermissions([Rank::GROUP_STAFF]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $player = strtolower($args["joueur"]);

        if (!isset(Cache::$bans[$player])) {
            $sender->sendMessage(Util::PREFIX . "Le joueur " . $player . " n'est pas banni (verifiez bien les caractères), le joueur peut être banni depuis une ip, un deviceId...");
            return;
        }

        $data = Cache::$bans[$player];
        $sender->sendMessage(Util::PREFIX . "Le joueur §9" . $player . " §fa été banni par §9" . $data[0] . "§f, raison: §9" . $data[2] . "§f, temps restant: §9" . Util::formatDurationFromSeconds($data[1] - time()));
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("joueur"));
    }
}