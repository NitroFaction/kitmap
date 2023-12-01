<?php

namespace Kitmap\task\repeat;

use Kitmap\command\player\Lottery;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\player\Player;
use pocketmine\Server;

class LotteryTask
{
    public static int $time = 7200;

    public static function run(): void
    {
        self::$time--;

        if (in_array(self::$time, [5400, 3600, 2700, 1800, 900, 600, 300, 60, 30, 5, 4, 3, 2, 1])) {
            Server::getInstance()->broadcastMessage(Util::PREFIX . "Le tirage de la lotterie s'effectuera dans §q" . self::formatLotteryRemainingTime() . " §f! La lotterie contient actuellement §q" . Util::formatNumberWithSuffix(Lottery::getTotalBets()) . " §fpièce(s) !");
        }

        if (self::$time <= 0) {
            $bets = Lottery::$bets;

            if (!empty($bets)) {
                $range = 0;
                $tickets = [];

                shuffle($bets);

                foreach (Lottery::$bets as $player => $bet) {
                    $range += $bet;
                    $tickets[$range] = $player;
                }

                $winTicket = mt_rand(0, $range);
                $saved = 0;

                foreach ($tickets as $range => $player) {
                    if ($winTicket >= $saved && $winTicket <= $range) {
                        $winner = $player;
                        break;
                    } else {
                        $saved = $range;
                    }
                }

                /* @noinspection PhpUndefinedVariableInspection */
                $playerWin = Server::getInstance()->getPlayerExact(str_replace("_", " ", $winner));

                if ($playerWin instanceof Player) {
                    $gain = Lottery::getTotalBets();

                    $session = Session::get($playerWin);
                    $session->addValue("money", $gain);

                    $playerWin->sendTitle("§q+ " . $gain . " +", "§7Vous venez de gagner " . Util::formatNumberWithSuffix($gain) . " pièces grâce à la lotterie !");
                    Server::getInstance()->broadcastMessage(Util::PREFIX . "Le joueur §q" . $playerWin->getName() . " §fremporte §q" . $gain . " §fpièces grâce à la lotterie !");
                }
            } else {
                Server::getInstance()->broadcastMessage(Util::PREFIX . "Aucun joueur n'a misé de pièces à la lotterie... Par conséquent, personne n'a gagné le gros lot !");
            }

            self::reset();
        }
    }

    public static function formatLotteryRemainingTime(): string
    {
        $time = self::$time;
        $units = ['h' => 3600, 'm' => 60, 's' => 1];
        $formatted = [];

        foreach ($units as $unit => $value) {
            if ($time >= $value) {
                [$quantity, $time] = [intdiv($time, $value), $time % $value];
                $formatted[] = $quantity . $unit;
            }
        }

        return implode('', $formatted);
    }

    private static function reset(): void
    {
        self::$time = 7200;
        Lottery::$bets = [];
    }

}