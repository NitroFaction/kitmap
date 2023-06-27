<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\tool;

use CortexPE\Commando\BaseCommand;
use Kitmap\command\player\util\faction\Bet;
use Kitmap\command\staff\money\Addvalue;
use Kitmap\handler\Cache;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;

class GiveBet extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "givebet",
            "Donner les récompenses aux parieurs qui ont eu juste"
        );

        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $list = Bet::getGamesData();

        foreach ($list as $value) {
            $home = $value["home"];
            $outside = $value["outside"];

            $id = $home . ":" . $outside;
            $bets = Cache::$data["bet"][$id] ?? false;

            $winner = $this->getWinner($value);

            if ($value["status"] !== "done" || $bets === false) {
                continue;
            }

            $rewards = match ($winner) {
                $home => 0,
                null => 1,
                $outside => 2,
            };

            $format = match ($rewards) {
                0 => "la victoire de §e" . $home,
                1 => "un §ematch nul",
                2 => "la victoire de §e" . $outside
            };

            foreach ($bets as $player => $data) {
                if ($data[2] === $rewards) {
                    Addvalue::addValue("Paris Sportif", strtolower($player), "money", $data[0] * $data[1]);
                }
            }

            unset(Cache::$data["bet"][$id]);
            Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Tous les joueurs ayant parié sur le match §e" . $home . " §f- §e" . $outside . " §fet qui ont parié sur §e" . $format . " §font gagnés et récupérés leurs récompenses ! Bravo à eux !");
        }
    }

    private function getWinner(array $value): ?string
    {
        $home = $value["home"];
        $outside = $value["outside"];

        list($homeScore, $outsideScore) = explode(":", $value["score"] ?? "0:0");

        $homeScore = intval($homeScore);
        $outsideScore = intval($outsideScore);

        return ($homeScore === $outsideScore) ? null : ($homeScore > $outsideScore ? $home : $outside);
    }

    protected function prepare(): void
    {
    }
}