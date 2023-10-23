<?php

/**
 * @noinspection PhpMultipleClassDeclarationsInspection
 * @noinspection PhpUnusedParameterInspection
 */

namespace Kitmap\handler;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\task\repeat\RouletteTask;
use Kitmap\Util;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\XpLevelUpSound;

class Casino
{

    public static array $games = [];

    public static function openCasinoForm(): SimpleForm
    {
        $form = new SimpleForm(function (Player $player, ?string $data = null) {
            if (is_string($data)) {
                $formToSend = in_array($data, ["roulette", "escalier", "mines"]) ? self::openCasinoGameForm($data) : null;
                if (!is_null($formToSend)) {
                    $player->sendForm($formToSend);
                }
            }
        });
        $form->setTitle("Casino");
        $form->setContent(Util::PREFIX . "Bienvenue dans le menu du §ecasino §f! Veuillez choisir le jeu auquel vous voulez jouer !");
        $form->addButton("§8Roulette", label: "roulette");
        $form->addButton("§8Escalier", label: "escalier");
        $form->addButton("§8Mines", label: "mines");
        return $form;
    }

    public static function openCasinoGameForm(string $game): SimpleForm
    {
        $form = new SimpleForm(function (Player $player, ?int $data = null) use ($game) {
            if (is_int($data)) {
                $formToSend = match ($data) {
                    0 => self::openBetForm($player, $game),
                    1 => self::openGameRulesForm($player, $game),
                    default => self::openCasinoForm()
                };
                $player->sendForm($formToSend);
            }
        });
        $form->setTitle(self::getGameName($game));
        $form->setContent(Util::PREFIX . "Bienvenue dans le menu du jeu §e" . self::getGameName($game) . " §f! Que voulez-vous faire ?");
        $form->addButton("§8Jouer");
        $form->addButton("§8Règles");
        return $form;
    }

    public static function openGameRulesForm(Player $player, string $game): SimpleForm
    {
        $form = new SimpleForm(function (Player $player, ?int $data = null) use ($game) {
            $player->sendForm(self::openCasinoGameForm($game));
        });
        $form->setTitle("Règles " . self::getGameName($game));
        $form->setContent(self::getRulesByGame($game));
        return $form;
    }

    public static function openBetForm(Player $player, string $game): CustomForm
    {
        $session = Session::get($player);
        $form = new CustomForm(function (Player $player, ?array $data = null) use ($session, $game) {
            if (is_array($data) && isset($data["bet"])) {
                if (ctype_digit($data["bet"])) {
                    if ($data["bet"] >= 10000) {
                        $money = $session->data["money"];
                        if ($money >= $data["bet"]) {
                            if (isset($data["color"])) {
                                if (intval($data["color"]) < 0 || intval($data["color"]) > 2) {
                                    $player->sendMessage(Util::PREFIX . "La couleur indiquée est invalide");
                                    return;
                                }
                            } else if (isset($data["mine"])) {
                                if (!ctype_digit($data["mine"])) {
                                    $player->sendMessage(Util::PREFIX . "Le nombre de mines indiqué est invalide");
                                    return;
                                }
                            }
                            $session->addValue("money", $data["bet"], true);
                            $playerName = strtolower($player->getName());
                            self::$games[$playerName] = [
                                "game" => $game,
                                "bet" => intval($data["bet"]),
                            ];
                            switch ($game) {
                                case "roulette":
                                    self::$games[$playerName]["color"] = $data["color"];
                                    break;
                                case "escalier":
                                case "mines":
                                    self::$games[$playerName]["score"] = 0;
                                    if ($game == "mines") {
                                        self::$games[$playerName]["mine"] = $data["mine"];
                                    }
                                    break;
                            }
                            self::startGame($player, $game, $data["bet"]);
                        } else {
                            $player->sendMessage(Util::PREFIX . "Votre monnaie est infèrieur à §e" . $data["bet"]);
                        }
                    } else {
                        $player->sendMessage(Util::PREFIX . "La mise minimale de ce jeu est 10k de pièces.");
                    }
                } else {
                    $player->sendMessage(Util::PREFIX . "Le prix indiqué est invalide");
                }
            }
        });
        $form->setTitle(self::getGameName($game));
        $form->addInput("Choisissez le montant à parier (min: " . Util::formatNumberWithSuffix(10000) . " | max: " . Util::formatNumberWithSuffix($session->data["money"]) . ")", default: "10000", label: "bet");
        switch ($game) {
            case "roulette":
                $form->addDropdown("Couleur sur laquelle parier", ["§cRouge", "§8Noir", "§aVert"], 0, "color");
                break;
            case "mines":
                $form->addInput("Nombre de mine(s)", default: "3", label: "mine");
                break;
        }
        return $form;
    }

    private static function startGame(Player $player, string $game, int $bet): void
    {
        switch ($game) {
            case "roulette":
                $roulette = [];
                for ($i = 0; $i <= 36; $i++) {
                    $block = VanillaBlocks::STAINED_CLAY();
                    $color = match (true) {
                        $i === 0 => DyeColor::LIME(),
                        $i % 2 === 0 => DyeColor::RED(),
                        default => DyeColor::BLACK()
                    };
                    $block->setColor($color);
                    $roulette[$i] = $block->asItem()->setCustomName(TextFormat::RESET . self::getColorNameByDyeColor($block->getColor()) . " #" . $i);
                }
                $invMenu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);
                $invMenu->setName(ucfirst($game));
                $invMenu->setListener(InvMenu::readonly());
                $invMenu->send($player);
                $invMenuInventory = $invMenu->getInventory();
                Main::getInstance()->getScheduler()->scheduleRepeatingTask(new RouletteTask($player, $invMenuInventory, $bet, $roulette), 2);
                break;
            case "escalier":
                break;
            case "mines":
                break;
        }
    }

    public static function winGame(Player $player, string $game, int $gain): void
    {
        $session = Session::get($player);
        $session->addValue("money", $gain);
        $player->sendTitle("§e+ " . $gain . " +", "§7Vous avez gagné " . $gain . " pièce(s) en jouant à " . self::getGameName($game) . " !");
        $player->sendMessage(Util::PREFIX . "Vous avez gagné §e" . $gain . " pièces §fen jouant à §e" . self::getGameName($game) . " §f!");
        $player->broadcastSound(new XpLevelUpSound(5));
        unset(self::$games[strtolower($player->getName())]);
    }

    public static function loseGame(Player $player, string $game): void
    {
        $player->sendMessage(Util::PREFIX . "Vous n'avez rien ganer en jouant au jeu " . ucfirst($game) . "...");
        unset(self::$games[strtolower($player->getName())]);
        // TODO: Trouver un son pour bien foutre le seum au joueur qui a perdu
    }

    private static function getRulesByGame(string $game): string
    {
        return match ($game) {
            "roulette" => "Au début de la partie, vous serez amené à choisir une option parmi 3 couleurs (§cRouge§f, §8Noir§f et §aVert§f) ! Dès lors que votre choix sera fait, une roulette tournera et une couleur sera aléatoirement choisie ! Si la couleur choisie est celle sur laquelle vous avez pariée au début, vous doublez votre mise, sinon, vous perdez tout !\n\nNOTE : La couleur verte n'apparait qu'une fois dans les 37 numéros de la roulette, il est donc très rare de tomber sur cette couleur. Si la roulette sélectionne la couleur verte et que vous avez parié dessus, votre mise initiale sera multipliée par 14 !"
        };
    }

    private static function getColorNameByDyeColor(DyeColor $dyeColor): string
    {
        return match ($dyeColor) {
            DyeColor::RED() => "§cRouge",
            DyeColor::BLACK() => "§8Noir",
            DyeColor::LIME() => "§aVert"
        };
    }

    private static function getGameName(string $name): string
    {
        return ucfirst($name);
    }

}
