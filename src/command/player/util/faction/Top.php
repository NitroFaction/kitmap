<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\faction;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use NCore\handler\Cache;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;

class Top extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "top",
            "Envoie la liste des meilleurs joueurs ou factions"
        );
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $i = 1;

        $page = !isset($args["page"]) ? 1 : $args["page"];
        $format = "§e{KEY} §8(§f{VALUE}§8)";

        switch ($args["opt"]) {
            case "kill":
                $top = self::getPlayersTopList("kill");
                $response = Util::arrayToMessage($top, $page, $format);

                $sender->sendMessage(Util::PREFIX . "Liste des joueurs ayant le plus de kills §f(Page §e#" . $page . "§f/§e" . $response[0] . "§f)");

                foreach ($response[1] as $value) {
                    $sender->sendMessage("§7" . (($page - 1) * 10) + $i . ". " . $value);
                    $i++;
                }
                break;
            case "money":
                $top = self::getPlayersTopList("money");
                $response = Util::arrayToMessage($top, $page, $format);

                $sender->sendMessage(Util::PREFIX . "Liste des joueurs ayant le plus d'argent §f(Page §e#" . $page . "§f/§e" . $response[0] . "§f)");

                foreach ($response[1] as $value) {
                    $sender->sendMessage("§7" . (($page - 1) * 10) + $i . ". " . $value);
                    $i++;
                }
                break;
            case "death":
                $top = self::getPlayersTopList("death");
                $response = Util::arrayToMessage($top, $page, $format);

                $sender->sendMessage(Util::PREFIX . "Liste des joueurs ayant le plus de morts §f(Page §e#" . $page . "§f/§e" . $response[0] . "§f)");

                foreach ($response[1] as $value) {
                    $sender->sendMessage("§7" . (($page - 1) * 10) + $i . ". " . $value);
                    $i++;
                }
                break;
            case "killstreak":
                $top = self::getPlayersTopList("killstreak");
                $response = Util::arrayToMessage($top, $page, $format);

                $sender->sendMessage(Util::PREFIX . "Liste des joueurs ayant le plus de kill sans mourrir §f(Page §e#" . $page . "§f/§e" . $response[0] . "§f)");

                foreach ($response[1] as $value) {
                    $sender->sendMessage("§7" . (($page - 1) * 10) + $i . ". " . $value);
                    $i++;
                }
                break;
            case "nerd":
                $top = self::getPlayersTopList("played_time");
                $response = Util::arrayToMessage($top, $page, $format, true);

                $sender->sendMessage(Util::PREFIX . "Liste des joueurs ayant joué le plus au serveur §f(Page §e#" . $page . "§f/§e" . $response[0] . "§f)");

                foreach ($response[1] as $value) {
                    $sender->sendMessage("§7" . (($page - 1) * 10) + $i . ". " . $value);
                    $i++;
                }
                break;
            case "faction":
                $top = self::getFactionTopList();
                $response = Util::arrayToMessage($top, $page, $format);

                $sender->sendMessage(Util::PREFIX . "Liste des meilleurs §efactions §f(Page §e#" . $page . "§f/§e" . $response[0] . "§f)");

                foreach ($response[1] as $value) {
                    $sender->sendMessage("§7" . (($page - 1) * 10) + $i . ". " . $value);
                    $i++;
                }
                break;
        }
    }

    public static function getPlayersTopList(string $category): array
    {
        $leaderboard = Cache::$players[$category] ?? [];
        $array = [];

        foreach ($leaderboard as $key => $value) {
            $upper = Cache::$players["upper_name"][$key] ?? $key;
            $array[$upper] = $value;
        }

        arsort($array);
        return $array;
    }

    public static function getFactionTopList(): array
    {
        $array = [];

        foreach (Cache::$factions as $value) {
            $array[$value["upper_name"]] = $value["power"];
        }

        arsort($array);
        return $array;
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("opt", ["killstreak", "kill", "money", "death", "faction", "nerd"]));
        $this->registerArgument(1, new IntegerArgument("page", true));
    }
}