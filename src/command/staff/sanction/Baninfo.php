<?php /** @noinspection PhpUnused */

namespace NCore\command\staff\sanction;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseCommand;
use NCore\handler\Cache;
use NCore\handler\SanctionAPI;
use NCore\Util;
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

        $this->setPermission("staff.group");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $player = strtolower($args["joueur"]);

        if (!isset(Cache::$bans[$player])) {
            $sender->sendMessage(Util::PREFIX . "Le joueur " . $player . " n'est pas banni (verifiez bien les caractères), le joueur peut être banni depuis une ip, un deviceId..");
            return;
        }

        $data = Cache::$bans[$player];
        $sender->sendMessage(Util::PREFIX . "Le joueur §e" . $player . " §fa été banni par §e" . $data[0] . "§f, raison: §e" . $data[2] . "§f, temps restant: §e" . SanctionAPI::format($data[1] - time()));
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("joueur"));
    }
}