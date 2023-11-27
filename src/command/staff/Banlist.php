<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\Rank;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;

class Banlist extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "banlist",
            "Affiche la liste des joueurs banni du serveur"
        );

        $this->setPermissions([Rank::GROUP_STAFF]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $list = $this->getBanList();
        $format = "§q{KEY}§f, raison: §q{REASON} §8(§f{TIME}§8)";

        $i = 1;
        $page = $args["page"] ?? 1;

        $response = Util::arrayToPage($list, $page, 10);
        $sender->sendMessage(Util::PREFIX . "Liste des joueurs banni du serveur §f(Page §q#" . $page . "§f/§q" . $response[0] . "§f)");

        foreach ($response[1] as $value) {
            $sender->sendMessage("§7" . (($page - 1) * 10) + $i . ". " . str_replace(["{KEY}", "{REASON}", "{TIME}"], [$value[3], $value[2], $value[1]], $format));
            $i++;
        }
    }

    private function getBanList(): array
    {
        $result = [];

        foreach (Cache::$bans as $key => $value) {
            $time = $value[1];

            if ($time > time()) {
                if (strlen(strval($key)) > 15 || str_contains(strval($key), ".")) {
                    continue;
                }

                $value[1] = Util::formatDurationFromSeconds($time - time());
                $value[3] = $key;
                $result[$key] = $value;
            } else {
                unset(Cache::$bans[$key]);
            }
        }
        return $result;
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new IntegerArgument("page", true));
    }
}