<?php

/**
 * @noinspection PhpMultipleClassDeclarationsInspection
 * @noinspection PhpUnusedLocalVariableInspection
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
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\block\Concrete;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\inventory\Inventory;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\DoorCrashSound;
use pocketmine\world\sound\ExplodeSound;
use pocketmine\world\sound\XpCollectSound;
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
                                    } else {
                                        self::$games[$playerName]["end-status"] = 0; // 0 = sort de l'inv, 1 = perdu la partie, 2 = clique sur le bloc jaune, 3 = complété à 100%
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
                    $block = VanillaBlocks::CONCRETE();
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
                $lines = [];
                $escalier = [];
                $indice = 9;
                for ($i = 0; $i <= 3; $i++) {
                    $patern = [0, 0, 0, 0];
                    $randomIndex = mt_rand(0, 3);
                    $patern[$randomIndex] = 1;
                    $escalier[$i] = $patern;
                    foreach (range(37, 40) as $slot) {
                        $lines[$i][] = ($slot - ($i * $indice));
                    }
                }
                $playerName = strtolower($player->getName());
                $invMenu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
                $invMenu->setName(ucfirst($game));
                $invMenuInventory = $invMenu->getInventory();
                foreach ($lines as $stage => $slots) {
                    foreach ($slots as $slot) {
                        $block = VanillaBlocks::CONCRETE()->setColor(DyeColor::BLACK())->asItem();
                        $block->setCustomName(TextFormat::colorize(" "));
                        $block->setLore([TextFormat::colorize(" ")]);
                        if ($stage === 0) {
                            $block->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(-1)));
                        }
                        $invMenuInventory->setItem($slot, $block);
                    }
                }
                $removeInventory = function (Player $player, int $endStatus) use ($playerName): void {
                    self::$games[$playerName]["end-status"] = $endStatus;
                    $player->removeCurrentWindow();
                };
                $setCollectGainBlocks = function (int $multiplier) use ($invMenuInventory, $bet): void {
                    foreach ([24, 25, 33, 34] as $collectGainSlot) {
                        $formattedBet = Util::formatNumberWithSuffix($bet);
                        $gainColor = ($possibleGain = ((($bet * 1.5) * $multiplier) * 0.90)) >= $bet ? TextFormat::GREEN : TextFormat::RED;
                        $collectGainBlock = VanillaBlocks::CONCRETE()->setColor(DyeColor::YELLOW())->asItem()->setCustomName("§r§l§e» §r§eRécupérer ses gains §l§e«§r\n§l§e| §r§fMise initial§8: §b" . $formattedBet . "\n§l§e| §r§fRécompense§8: " . $gainColor . Util::formatNumberWithSuffix(round($possibleGain)) . " §8(§7x" . round(($multiplier * 1.5) * 0.90) . "§8)");
                        $invMenuInventory->setItem($collectGainSlot, $collectGainBlock);
                    }
                };
                $setCollectGainBlocks(0);
                $invMenu->setListener(InvMenu::readonly(function (DeterministicInvMenuTransaction $invMenuTransaction) use ($invMenuInventory, $playerName, $bet, $escalier, $lines, $game, $removeInventory, $setCollectGainBlocks): void {
                    $player = $invMenuTransaction->getPlayer();
                    $itemClicked = $invMenuTransaction->getItemClicked();
                    $itemSlot = $invMenuTransaction->getAction()->getSlot();

                    $multiplier = self::$games[$playerName]["score"];
                    $interactibleSlots = array_merge($lines[$multiplier], [24, 25, 33, 34]);

                    if (in_array($itemSlot, array_merge($lines[$multiplier], [24, 25, 33, 34]))) {
                        if ($itemClicked->getBlock()->hasSameTypeId(VanillaBlocks::CONCRETE())) {
                            /* @var Concrete $blockClicked */
                            $blockClicked = clone $itemClicked->getBlock();
                            $blockClickedColor = $blockClicked->getColor();
                            if ($blockClickedColor->equals(DyeColor::BLACK())) {
                                $indiceIndex = ($itemSlot % 9) - 1;
                                $selectedSlot = $escalier[$multiplier][$indiceIndex];
                                if ($selectedSlot > 0) {
                                    $player->broadcastSound(new XpCollectSound());
                                    foreach ($lines[$multiplier] as $index => $slot) {
                                        $blockToSet = $index === $indiceIndex
                                            ? VanillaBlocks::CONCRETE()->setColor(DyeColor::LIME())->asItem()
                                            : VanillaBlocks::CONCRETE()->setColor(DyeColor::RED())->asItem();
                                        $blockToSet->setCustomName(TextFormat::colorize(" "));
                                        $blockToSet->setLore([TextFormat::colorize(" ")]);
                                        $invMenuInventory->setItem($slot, $blockToSet);
                                    }
                                    self::$games[$playerName]["score"]++;
                                    $updatedMultiplier = self::$games[$playerName]["score"];
                                    if ($updatedMultiplier === 4) {
                                        $removeInventory($player, 3);
                                        return;
                                    }
                                    foreach ($lines[$updatedMultiplier] as $_ => $slot) {
                                        $newLineEnchanttedBlock = clone $invMenuInventory->getItem($slot);
                                        $newLineEnchanttedBlock->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(-1)));
                                        $invMenuInventory->setItem($slot, $newLineEnchanttedBlock);
                                    }
                                    $setCollectGainBlocks($updatedMultiplier);
                                } else {
                                    $player->broadcastSound(new ExplodeSound(), [$player]);
                                    $removeInventory($player, 1);
                                }
                            } else if ($blockClickedColor->equals(DyeColor::YELLOW())) {
                                $removeInventory($player, 2);
                            } else {
                                $player->broadcastSound(new DoorCrashSound(), [$player]);
                            }
                        } else {
                            $player->broadcastSound(new DoorCrashSound(), [$player]);
                        }
                    } else {
                        $player->broadcastSound(new DoorCrashSound(), [$player]);
                    }
                }));
                $invMenu->setInventoryCloseListener(function (Player $player, Inventory $inventory) use ($game, $playerName, $bet): void {
                    $data = self::$games[$playerName];
                    $multiplier = $data["score"];
                    $gain = (($bet * 1.5) * $multiplier);
                    $endStatus = $data["end-status"];
                    switch ($endStatus) {
                        case 0:
                            Session::get($player)->addValue("money", $bet);
                            $player->sendMessage(Util::PREFIX . "Votre mise dans l'escalier a été annulée, vous venez de récupérer votre mise initiale");
                            break;
                        case 1:
                        case 2:
                            $gain > $bet ? self::winGame($player, $game, $gain, $multiplier) : self::loseGame($player, $game);
                            break;
                        case 3:
                            self::winGame($player, $game, $gain, $multiplier);
                            break;
                    }
                    unset(self::$games[$playerName]);
                });
                $invMenu->send($player);
                break;
            case "mines":
                break;
        }
    }

    public static function winGame(Player $player, string $game, int $gain, int $multiplier = 0): void
    {
        $session = Session::get($player);
        $finalGain = round($gain * 0.90);
        $formattedFinalGain = Util::formatNumberWithSuffix($finalGain);
        $session->addValue("money", $finalGain);
        $playerName = strtolower($player->getName());
        $player->sendTitle("§e+ " . $finalGain . " +", "§7Vous avez gagné " . $formattedFinalGain . " pièce(s) en jouant à " . self::getGameName($game) . " !");
        switch ($game) {
            case "roulette":
                Server::getInstance()->broadcastMessage(Util::PREFIX . "§e" . $player->getName() . " §fa remporté §e" . $formattedFinalGain . " pièces §fen pariant sur la couleur " . self::getColorNameById(self::$games[$playerName]["color"]) . " §fà la §eRoulette §f! §8(§e/casino§8)");
                break;
            case "escalier":
                Server::getInstance()->broadcastMessage(Util::PREFIX . "§e" . $player->getName() . " §fa remporté §e" . $formattedFinalGain . " pièces §fen réussissant §e" . $multiplier . " palier(s) §fdans l'§eEscalier §f! §8(§e/casino§8)");
                break;
        }
        $player->broadcastSound(new XpLevelUpSound(5));
        unset(self::$games[$playerName]);
    }

    public static function loseGame(Player $player, string $game): void
    {
        $player->sendMessage(Util::PREFIX . "Vous n'avez rien gagner en jouant au jeu " . ucfirst($game) . "...");
        unset(self::$games[strtolower($player->getName())]);
        // TODO: Trouver un son pour bien foutre le seum au joueur qui a perdu
    }

    private static function getRulesByGame(string $game): string
    {
        return match ($game) {
            "roulette" => "Au début de la partie, vous serez amené à choisir une option parmi 3 couleurs (§cRouge§f, §8Noir§f et §aVert§f) ! Dès lors que votre choix sera fait, une roulette tournera et une couleur sera aléatoirement choisie ! Si la couleur choisie est celle sur laquelle vous avez pariée au début, vous doublez votre mise, sinon, vous perdez tout !\n\nNOTE : La couleur verte n'apparait qu'une fois dans les 37 numéros de la roulette, il est donc très rare de tomber sur cette couleur. Si la roulette sélectionne la couleur verte et que vous avez parié dessus, votre mise initiale sera multipliée par 14 !",
            "escalier" => "Dans ce jeu, vous commencez au palier 0 ! Le but est de faire le bon choix parmi 4 blocks noirs ! Ils sont tous identiques, mais derrière l'un d'eux se cache un block vert. Les autres cachent tous des blocks rouges. Le but du jeu est de gravir les différents palliers et de trouver tous les blocks verts de chaque étage !"
        };
    }

    private static function getColorNameById(int $id): string
    {
        return match ($id) {
            0 => "§cRouge",
            1 => "§8Noir",
            2 => "§aVert"
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
