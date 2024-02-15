<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff;

use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;

class Question extends BaseCommand
{
    public static int $currentEvent = 0;
    public static mixed $currentReply = null;

    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "question",
            "Lance une question dans le chat !"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        switch (mt_rand(1, 3)) {
            case 1:
                $code = substr(str_shuffle(str_repeat("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ", 5)), 0, 10);

                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Soyez le première à réécrire le code pour gagner §95k$ §f! Code: §9" . $code);

                self::$currentEvent = 1;
                self::$currentReply = $code;
                break;
            case 2:
                $array = Cache::$config["words-list"];
                $word = $array[array_rand($array)];

                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Soyez le première à trouver le bon mot pour gagner §95k$ §f! Mot: §9" . str_shuffle($word));

                self::$currentEvent = 2;
                self::$currentReply = $word;
                break;
            case 3:
                $arr = ["+", "-", "*"];
                $method = $arr[array_rand($arr)];

                $number1 = mt_rand(1, 99);
                $number2 = mt_rand(1, 99);

                $result = 0;

                switch ($method) {
                    case "+":
                        $result = $number1 + $number2;
                        break;
                    case "-":
                        $result = $number1 - $number2;
                        break;
                    case "*":
                        $result = $number1 * $number2;
                        break;
                }

                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Soyez le première à répondre au calcul pour gagner §95k$ §f! Calcul: §9" . $number1 . " " . $method . " " . $number2);

                self::$currentEvent = 3;
                self::$currentReply = $result;
                break;
        }
    }

    protected function prepare(): void
    {
    }
}