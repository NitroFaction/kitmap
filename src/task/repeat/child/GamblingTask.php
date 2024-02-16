<?php

namespace Kitmap\task\repeat\child;

use Kitmap\command\player\Gambling;
use Kitmap\handler\Cache;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Location;
use pocketmine\item\PotionType;
use pocketmine\item\VanillaItems;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\world\sound\ClickSound;
use pocketmine\world\sound\ExplodeSound;

class GamblingTask
{
    public static array $players = [];
    public static array $settings = [];
    public static array $saves = [];

    public static ?Player $player1 = null;
    public static ?Player $player2 = null;

    public static bool $currently = false;
    public static int $since = 0;

    public static function run(): void
    {
        if (!self::$currently) {
            return;
        }

        foreach (Main::getInstance()->getServer()->getOnlinePlayers() as $p) {
            if (Util::insideZone($p->getPosition(), "gambling") && !in_array($p->getName(), self::$players)) {
                $p->teleport(Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
            }
        }

        if (self::$since > 600) {
            self::stop();
        }

        $player = self::$player1;
        $target = self::$player2;

        if (0 > self::$since) {
            foreach ([$player, $target] as $p) {
                $p->getXpManager()->setXpAndProgress(0, 0);
                $p->getEffects()->clear();
                $p->setHealth(20);

                $p->getArmorInventory()->clearAll();
                $p->getInventory()->clearAll();
                $p->getOffHandInventory()->clearAll();
                $p->getCursorInventory()->clearAll();
                $p->getCraftingGrid()->clearAll();

                Util::removeCurrentWindow($p);

                $p->sendTitle("§eDébut dans " . 5 - (self::$since + 5));
                $p->setNoClientPredictions();
                $p->setGamemode(GameMode::SURVIVAL());

                $p->broadcastSound(new ClickSound());
            }

            $player->teleport(self::getPosition(1));
            $target->teleport(self::getPosition(2));
        } else if (self::$since === 0) {
            foreach ([$player, $target] as $p) {
                if (Session::get($p)->data["night_vision"]) {
                    $p->getEffects()->add(new EffectInstance(VanillaEffects::NIGHT_VISION(), 20 * 60 * 60 * 24, 255, false));
                }

                Util::addItems($p, Gambling::getKit(self::$settings["kit"]), false);

                $p->setNoClientPredictions(false);
                $p->broadcastSound(new ExplodeSound());
            }

            $player->sendTitle("§4C'est parti !!!", "§7Vous affrontez " . $player->getName());
            $target->sendTitle("§4C'est parti !!!", "§7Vous affrontez " . $player->getName());
        } else {
            if (!Util::insideZone($player->getPosition(), "gambling")) {
                $player->teleport(self::getPosition(1));
            } else if (!Util::insideZone($target->getPosition(), "gambling")) {
                $target->teleport(self::getPosition(2));
            }
        }

        self::$since++;
    }

    private static function getPosition(int $number): Location
    {
        $world = Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld();

        $name = match ($number) {
            2 => "second-spawn",
            default => "first-spawn"
        };

        [$x, $y, $z, $yaw] = explode(":", Cache::$config["gambling"][$name]);
        return new Location(floatval($x), floatval($y), floatval($z), $world, $yaw, 0);
    }

    public static function stop(string $winner = null): void
    {
        if (!self::$currently) {
            return;
        }

        $winnerPot = "?";
        $loserPot = "?";

        foreach ([self::$player1, self::$player2] as $p) {
            if ($p instanceof Player) {
                if ($winner === $p->getName()) {
                    $winnerPot = Util::getItemCount($p, VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_HEALING()));
                } else {
                    $loserPot = Util::getItemCount($p, VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_HEALING()));
                }

                Session::get($p)->removeCooldown("combat");

                $position = Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation();
                $p->teleport($position);

                Util::removeCurrentWindow($p);

                $p->getArmorInventory()->clearAll();
                $p->getInventory()->clearAll();
                $p->getOffHandInventory()->clearAll();
                $p->getCursorInventory()->clearAll();
                $p->getCraftingGrid()->clearAll();

                $p->setNoClientPredictions(false);
                $p->broadcastSound(new ExplodeSound());

                Util::restorePlayer($p, self::$saves[$p->getName()]);
            }
        }

        $bet = self::$settings["bet"];

        if (!is_null($winner)) {
            $loser = (self::$players[0] === $winner) ? self::$players[1] : self::$players[0];

            $message = $bet > 1 ? " Le prix de la victoire était de §9" . Util::formatNumberWithSuffix($bet * 2) . " §fpièces !" : "";
            Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§9" . $winner . "[§7" . $winnerPot . "§9] §fvient de gagner un gambling ou il affrontait §9" . $loser . "[§7" . $loserPot . "§9] §f!" . $message);

            if (($p = Main::getInstance()->getServer()->getPlayerExact($winner)) instanceof Player) {
                Session::get($p)->addValue("money", $bet * 2);
            }
        } else {
            Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le gambling affrontant §9" . self::$players[0] . " §fet §9" . self::$players[1] . " §fa été annulé");

            if ($bet > 1) {
                Util::addValue("CONSOLE", strtolower(self::$players[0]), "money", $bet);
                Util::addValue("CONSOLE", strtolower(self::$players[1]), "money", $bet);
            }
        }

        self::$players = [];
        self::$settings = [];
        self::$saves = [];

        self::$player1 = null;
        self::$player2 = null;

        self::$currently = false;
        self::$since = 0;
    }
}