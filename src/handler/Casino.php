<?php

namespace Kitmap\handler;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\task\RouletteTask;
use Kitmap\Util;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\block\Concrete;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\ItemBlock;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
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
        $form = new SimpleForm(function (Player $player, mixed $data) {
            if (!is_string($data)) {
                return;
            }

            $formToSend = in_array($data, ["roulette", "dragon-tower", "mines"]) ? self::openCasinoGameForm($data) : null;

            if (!is_null($formToSend)) {
                $player->sendForm($formToSend);
            }
        });
        $form->setTitle("Casino");
        $form->setContent(Util::PREFIX . "Bienvenue dans le menu du §6casino §f! Veuillez choisir le jeu auquel vous voulez jouer !");
        $form->addButton("§8Roulette", label: "roulette");
        $form->addButton("§8Dragon Tower", label: "dragon-tower");
        $form->addButton("§8Mines", label: "mines");
        return $form;
    }

    public static function openCasinoGameForm(string $game): SimpleForm
    {
        $form = new SimpleForm(function (Player $player, mixed $data) use ($game) {
            if (!is_int($data)) {
                return;
            }

            $formToSend = match ($data) {
                0 => self::openBetForm($player, $game),
                1 => self::openGameRulesForm($game),
                default => self::openCasinoForm()
            };

            $player->sendForm($formToSend);
        });
        $form->setTitle(self::getGameName($game));
        $form->setContent(Util::PREFIX . "Bienvenue dans le menu du jeu §6" . self::getGameName($game) . " §f! Que voulez-vous faire ?");
        $form->addButton("§8Jouer");
        $form->addButton("§8Règles");
        return $form;
    }

    public static function openBetForm(Player $player, string $game): CustomForm
    {
        $session = Session::get($player);

        $form = new CustomForm(function (Player $player, mixed $data) use ($session, $game) {
            if (!is_array($data) || !isset($data["bet"])) {
                return;
            } else if (!is_numeric($data["bet"])) {
                $player->sendMessage(Util::PREFIX . "Le prix indiqué est invalide");
                return;
            }

            $amount = intval($data["bet"]);

            if ($amount < 10000) {
                $player->sendMessage(Util::PREFIX . "La mise minimale de ce jeu est 10k de pièces");
                return;
            } else if ($session->data["money"] < $amount) {
                $player->sendMessage(Util::PREFIX . "Votre monnaie est infèrieur à §6" . $amount);
                return;
            }

            if (isset($data["color"])) {
                if (intval($data["color"]) < 0 || intval($data["color"]) > 2) {
                    $player->sendMessage(Util::PREFIX . "La couleur indiquée est invalide");
                    return;
                }
            } else if (isset($data["mine"])) {
                if (!is_numeric($data["mine"])) {
                    $player->sendMessage(Util::PREFIX . "Le nombre de mines indiqué est invalide");
                    return;
                } else if ($data["mine"] <= 1 || $data["mine"] > 24) {
                    $player->sendMessage(Util::PREFIX . "Vous pouvez uniquement définir entre 2 et 24 mine(s)");
                    return;
                }
            }

            $session->addValue("money", $amount, true);
            $playerName = strtolower($player->getName());

            self::$games[$playerName] = [
                "game" => $game,
                "bet" => $amount,
            ];

            switch ($game) {
                case "roulette":
                    self::$games[$playerName]["color"] = $data["color"];
                    break;
                case "dragon-tower":
                case "mines":
                    self::$games[$playerName]["score"] = 0;
                    self::$games[$playerName]["end-status"] = 0;

                    if ($game == "mines") {
                        self::$games[$playerName]["mine"] = $data["mine"];
                    }
                    break;
            }

            if ($game === "roulette") {
                self::roulette($player, $game, $amount);
            } else if ($game === "dragon-tower") {
                self::dragonTower($player, $game, $amount);
            } else if ($game === "mines") {
                self::mines($player, $game, $amount);
            }
        });

        $form->setTitle(self::getGameName($game));
        $form->addInput("Choisissez le montant à parier", default: "10000", label: "bet");

        if ($game === "roulette") {
            $form->addDropdown("Couleur sur laquelle parier", ["§cRouge", "§8Noir", "§aVert"], 0, "color");
        } else if ($game === "mines") {
            $form->addSlider("Nombre de mine(s) : ", 2, 24, -1, 3, "mine");
        }

        return $form;
    }

    private static function roulette(Player $player, string $game, float $bet): void
    {
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
    }

    private static function getColorNameByDyeColor(DyeColor $dyeColor): string
    {
        return match ($dyeColor) {
            DyeColor::RED() => "§cRouge",
            DyeColor::BLACK() => "§8Noir",
            DyeColor::LIME() => "§aVert"
        };
    }

    private static function dragonTower(Player $player, string $game, float $bet): void
    {
        $playerName = strtolower($player->getName());

        $lines = [];
        $dt = [];
        $indice = 9;

        for ($i = 0; $i <= 3; $i++) {
            $patern = [0, 0, 0, 0];
            $randomIndex = mt_rand(0, 3);

            $patern[$randomIndex] = 1;
            $dt[$i] = $patern;

            foreach (range(37, 40) as $slot) {
                $lines[$i][] = ($slot - ($i * $indice));
            }
        }

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

        $setCollectGainBlocks = function (float $multiplier) use ($invMenuInventory, $bet): void {
            foreach ([24, 25, 33, 34] as $collectGainSlot) {
                $formattedBet = Util::formatNumberWithSuffix($bet);

                $possibleGain = (($bet * 1.5) * $multiplier) * 0.90;
                $possibleMultiplier = round(($possibleGain * 0.90) / $bet, 2);

                $gainColor = $possibleGain >= $bet ? TextFormat::GREEN : TextFormat::RED;

                $collectGainBlock = VanillaBlocks::CONCRETE()->setColor(DyeColor::YELLOW())->asItem()->setCustomName("§r§l§6» §r§6Récupérer ses gains §l§6«§r\n§l§6| §r§fMise initial§8: §b" . $formattedBet . "\n§l§6| §r§fRécompense§8: " . $gainColor . Util::formatNumberWithSuffix(round($possibleGain)) . " §8(§7x" . $possibleMultiplier . "§8)");
                $invMenuInventory->setItem($collectGainSlot, $collectGainBlock);
            }
        };

        $setCollectGainBlocks(0);

        $invMenu->setListener(InvMenu::readonly(function (DeterministicInvMenuTransaction $invMenuTransaction) use ($invMenuInventory, $playerName, $bet, $dt, $lines, $game, $setCollectGainBlocks): void {
            $player = $invMenuTransaction->getPlayer();
            $itemClicked = $invMenuTransaction->getItemClicked();
            $itemSlot = $invMenuTransaction->getAction()->getSlot();

            $multiplier = self::$games[$playerName]["score"];

            if (!in_array($itemSlot, array_merge($lines[$multiplier], [24, 25, 33, 34]))) {
                $player->broadcastSound(new DoorCrashSound(), [$player]);
                return;
            } else if (!$itemClicked->getBlock()->hasSameTypeId(VanillaBlocks::CONCRETE())) {
                $player->broadcastSound(new DoorCrashSound(), [$player]);
                return;
            }

            /* @var Concrete $blockClicked */
            $blockClicked = clone $itemClicked->getBlock();
            $blockClickedColor = $blockClicked->getColor();

            if ($blockClickedColor->equals(DyeColor::BLACK())) {
                $indiceIndex = ($itemSlot % 9) - 1;
                $selectedSlot = $dt[$multiplier][$indiceIndex];

                if ($selectedSlot > 0) {
                    $player->broadcastSound(new XpCollectSound());

                    foreach ($lines[$multiplier] as $index => $slot) {
                        $blockToSet = $index === $indiceIndex ? VanillaBlocks::CONCRETE()->setColor(DyeColor::LIME())->asItem() : VanillaBlocks::CONCRETE()->setColor(DyeColor::RED())->asItem();

                        $blockToSet->setCustomName(TextFormat::colorize(" "));
                        $blockToSet->setLore([TextFormat::colorize(" ")]);

                        $invMenuInventory->setItem($slot, $blockToSet);
                    }

                    self::$games[$playerName]["score"]++;
                    $updatedMultiplier = self::$games[$playerName]["score"];

                    if ($updatedMultiplier === 4) {
                        self::closeInventory($player, 3);
                        return;
                    }

                    foreach ($lines[$updatedMultiplier] as $slot) {
                        $newLineEnchanttedBlock = clone $invMenuInventory->getItem($slot);
                        $newLineEnchanttedBlock->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(-1)));
                        $invMenuInventory->setItem($slot, $newLineEnchanttedBlock);
                    }

                    $setCollectGainBlocks($updatedMultiplier);
                } else {
                    $player->broadcastSound(new ExplodeSound(), [$player]);
                    self::closeInventory($player, 1);
                }
            } else if ($blockClickedColor->equals(DyeColor::YELLOW())) {
                self::closeInventory($player, 2);
            } else {
                $player->broadcastSound(new DoorCrashSound(), [$player]);
            }
        }));

        $invMenu->setInventoryCloseListener(function (Player $player) use ($game, $playerName, $bet): void {
            $data = self::$games[$playerName];
            $multiplier = $data["score"];

            $gain = (($bet * 1.5) * $multiplier);
            $endStatus = $data["end-status"];

            switch ($endStatus) {
                case 0:
                    Session::get($player)->addValue("money", $bet);
                    $player->sendMessage(Util::PREFIX . "Votre mise au dragon tower a été annulée, vous venez de récupérer votre mise initiale");
                    break;
                case 1:
                    self::loseGame($player, $game, $bet);
                    break;
                case 2:
                    $gain > $bet ? self::winGame($player, $game, $gain, $multiplier) : self::loseGame($player, $game, $bet);
                    break;
                case 3:
                    self::winGame($player, $game, $gain, $multiplier);
                    break;
            }

            unset(self::$games[$playerName]);
        });

        $invMenu->send($player);
    }

    private static function closeInventory(Player $player, int $endStatus): void
    {
        self::$games[strtolower($player->getName())]["end-status"] = $endStatus;
        Util::removeCurrentWindow($player);
    }

    public static function loseGame(Player $player, string $game, float $bet): void
    {
        $suffix = match ($game) {
            "roulette" => "à la",
            "mines" => "aux",
            default => "au"
        };

        $game = ucwords(implode(" ", explode("-", $game)));

        if ($bet > 99999) {
            Server::getInstance()->broadcastMessage(Util::PREFIX . "§6" . $player->getName() . " §fa perdu §6" . Util::formatNumberWithSuffix($bet) . " pièces " . $suffix . " " . $game);
        }

        $player->sendMessage(Util::PREFIX . "Vous n'avez rien gagné en jouant " . $suffix . " " . $game);
        $player->broadcastSound(new ExplodeSound(), [$player]);

        unset(self::$games[strtolower($player->getName())]);
    }

    public static function winGame(Player $player, string $game, float $gain, int $score = 0, int $scoreToComplete = 0, float $multiplier = 0.0, float $bet = 0.0): void
    {
        $session = Session::get($player);
        $finalGain = round(($gain + $bet) * 0.90);

        $formattedFinalGain = Util::formatNumberWithSuffix($finalGain - $bet);
        $session->addValue("money", $finalGain);

        $playerName = strtolower($player->getName());
        $player->sendTitle("§6+ " . $finalGain . " +", "§7Vous avez gagné " . $formattedFinalGain . " pièce(s) en jouant à " . self::getGameName($game) . " !");

        switch ($game) {
            case "roulette":
                Server::getInstance()->broadcastMessage(Util::PREFIX . "§6" . $player->getName() . " §fa remporté §6" . $formattedFinalGain . " pièces §fen pariant sur la couleur " . self::getColorNameById(self::$games[$playerName]["color"]) . " §fà la §6Roulette §f! §8(§6/casino§8)");
                break;
            case "dragon-tower":
                Server::getInstance()->broadcastMessage(Util::PREFIX . "§6" . $player->getName() . " §fa remporté §6" . $formattedFinalGain . " pièces §fen réussissant §6" . $score . " palier(s) §fau §6Dragon Tower §f! §8(§6/casino§8)");
                break;
            case "mines":
                if ($multiplier > 1) {
                    Server::getInstance()->broadcastMessage(Util::PREFIX . "§6" . $player->getName() . " §fa remporté §6" . $formattedFinalGain . " pièces §8[§7x" . $multiplier . "§8] §fen esquivant §6" . $score . "/" . $scoreToComplete . " mine(s) §fdans les §6Mines §f! §8(§6/casino§8)");
                }
                break;
        }

        $player->broadcastSound(new XpLevelUpSound(5));
        unset(self::$games[$playerName]);
    }

    private static function getGameName(string $name): string
    {
        return ucfirst($name);
    }

    private static function getColorNameById(int $id): string
    {
        return match ($id) {
            0 => "§cRouge",
            1 => "§8Noir",
            2 => "§aVert"
        };
    }

    private static function mines(Player $player, string $game, float $bet): void
    {
        $playerName = strtolower($player->getName());

        $slots = [];
        $mines = [];

        for ($i = 0; $i <= 4; $i++) {
            foreach (range(2, 6) as $slot) {
                $finalSlot = $slot + ($i * 9);
                $mines[$finalSlot] = 0;
                $slots[] = $finalSlot;
            }
        }

        $mineAmount = self::$games[$playerName]["mine"];
        shuffle($slots);

        $randomSlots = array_slice($slots, 0, $mineAmount);

        foreach ($randomSlots as $randomSlot) {
            $mines[$randomSlot] = 1;
        }

        $scoreToComplete = 25 - $mineAmount;
        $invMenu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);

        $invMenu->setName(ucfirst($game));
        $invMenuInventory = $invMenu->getInventory();

        foreach (array_keys($mines) as $slot) {
            $block = VanillaBlocks::CONCRETE()->setColor(DyeColor::BLACK())->asItem();

            $block->setCustomName(TextFormat::colorize(" "));
            $block->setLore([TextFormat::colorize(" ")]);

            $invMenuInventory->setItem($slot, $block);
        }

        $updateCollectBlock = function (float $score) use ($invMenuInventory, $scoreToComplete, $bet): void {
            $formattedBet = Util::formatNumberWithSuffix($bet);

            if ($score > 0) {
                $multiplier = max(1, round((round(25 / $scoreToComplete, 2) - 1) * $score, 2) + 1);
            } else {
                $multiplier = 1;
            }

            $possibleGain = round((($bet * $multiplier) - $bet) * 0.90, 2) * 0.90;
            $multiplier = round((($multiplier - 1) * 0.90) + 1, 2);

            $collectGainBlock = VanillaBlocks::CONCRETE()->setColor(DyeColor::YELLOW())->asItem()->setCustomName("§r§l§6» §r§6Récupérer ses gains §l§6«§r\n§l§6| §r§fMise initial§8: §b" . $formattedBet . "\n§l§6| §r§fRécompense§8: §a" . Util::formatNumberWithSuffix($possibleGain) . " §8(§7x" . $multiplier . "§8)");
            $invMenuInventory->setItem(49, $collectGainBlock);
        };

        $updateCollectBlock(0);

        $invMenu->setListener(InvMenu::readonly(function (DeterministicInvMenuTransaction $invMenuTransaction) use ($invMenuInventory, $playerName, $bet, $mines, $scoreToComplete, $game, $updateCollectBlock): void {
            $player = $invMenuTransaction->getPlayer();
            $itemClicked = $invMenuTransaction->getItemClicked();
            $itemSlot = $invMenuTransaction->getAction()->getSlot();

            $interactibleSlots = array_merge(array_keys($mines), [49]);

            if (!in_array($itemSlot, $interactibleSlots)) {
                $player->broadcastSound(new DoorCrashSound(), [$player]);
                return;
            } else if (!$itemClicked->getBlock()->hasSameTypeId(VanillaBlocks::CONCRETE())) {
                $player->broadcastSound(new DoorCrashSound(), [$player]);
                return;
            }

            /* @var Concrete $blockClicked */
            $blockClicked = clone $itemClicked->getBlock();
            $blockClickedColor = $blockClicked->getColor();

            if ($blockClickedColor->equals(DyeColor::BLACK())) {
                $slotValue = $mines[$itemSlot];

                if ($slotValue < 1) {
                    $player->broadcastSound(new XpCollectSound(), [$player]);
                    $blockToSet = VanillaBlocks::CONCRETE()->setColor(DyeColor::LIME())->asItem();

                    $blockToSet->setCustomName(TextFormat::colorize(" "));
                    $blockToSet->setLore([TextFormat::colorize(" ")]);

                    $blockToSet->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(-1)));
                    $invMenuInventory->setItem($itemSlot, $blockToSet);

                    self::$games[$playerName]["score"]++;
                    $updatedMultiplier = self::$games[$playerName]["score"];

                    if ($updatedMultiplier >= $scoreToComplete) {
                        self::closeInventory($player, 3);
                        return;
                    }

                    $updateCollectBlock($updatedMultiplier);
                } else {
                    $player->broadcastSound(new ExplodeSound(), [$player]);
                    $tntToSet = VanillaBlocks::TNT()->asItem();

                    $tntToSet->setCustomName(TextFormat::colorize(" "));
                    $tntToSet->setLore([TextFormat::colorize(" ")]);
                    $tntToSet->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(-1)));

                    $invMenuInventory->setItem($itemSlot, $tntToSet);
                    self::$games[$playerName]["end-status"] = 1;

                    foreach (array_keys($mines) as $slot) {
                        $item = $invMenuInventory->getItem($slot);

                        if ($item instanceof ItemBlock) {
                            $itemBlock = clone $item->getBlock();

                            if ($itemBlock->hasSameTypeId(VanillaBlocks::CONCRETE())) {
                                /* @var Concrete $itemBlock */
                                $itemBlockColor = $itemBlock->getColor();

                                if ($itemBlockColor->equals(DyeColor::BLACK())) {
                                    $blockValue = $mines[$slot];
                                    $blockToSet = $blockValue < 1 ? VanillaBlocks::CONCRETE()->setColor(DyeColor::LIME())->asItem() : VanillaBlocks::TNT()->asItem();
                                    $invMenuInventory->setItem($slot, $blockToSet);
                                }
                            }
                        }
                    }

                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player, $playerName): void {
                        if (!is_null($player->getCurrentWindow()) && array_key_exists($playerName, self::$games)) {
                            self::closeInventory($player, 1);
                        }
                    }), 20 * 5);
                }
            } else if ($blockClickedColor->equals(DyeColor::YELLOW())) {
                if (self::$games[$playerName]["end-status"] === 0) {
                    self::closeInventory($player, 2);
                } else {
                    $player->broadcastSound(new DoorCrashSound(), [$player]);
                }
            } else {
                $player->broadcastSound(new DoorCrashSound(), [$player]);
            }
        }));

        $invMenu->setInventoryCloseListener(function (Player $player) use ($game, $playerName, $bet): void {
            $data = self::$games[$playerName];

            $scoreToComplete = 25 - $data["mine"];
            $score = $data["score"];

            $multiplier = max(1, round((round(25 / $scoreToComplete, 2) - 1) * $score, 2) + 1);

            $gain = round((($bet * $multiplier) - $bet) * 0.90, 2);
            $finalMultiplier = round((($multiplier - 1) * 0.90) + 1, 2);

            switch ($data["end-status"]) {
                case 0:
                    Session::get($player)->addValue("money", $bet);
                    $player->sendMessage(Util::PREFIX . "Votre mise dans les Mines a été annulée, vous venez de récupérer votre mise initiale");
                    break;
                case 1:
                    self::loseGame($player, $game, $bet);
                    break;
                case 2:
                    $gain > 0 ? self::winGame($player, $game, $gain, $score, $scoreToComplete, $finalMultiplier, $bet) : self::loseGame($player, $game, $bet);
                    break;
                case 3:
                    self::winGame($player, $game, $gain, $score, $scoreToComplete, $finalMultiplier, $bet);
                    break;
            }

            unset(self::$games[$playerName]);
        });

        $invMenu->send($player);
    }

    public static function openGameRulesForm(string $game): SimpleForm
    {
        $form = new SimpleForm(function (Player $player) use ($game) {
            $player->sendForm(self::openCasinoGameForm($game));
        });

        $form->setTitle("Règles " . self::getGameName($game));
        $form->setContent(self::getRulesByGame($game));

        return $form;
    }

    private static function getRulesByGame(string $game): string
    {
        return match ($game) {
            "roulette" => "Au début de la partie, vous serez amené à choisir une option parmi 3 couleurs (§cRouge§f, §8Noir§f et §aVert§f) ! Dès lors que votre choix sera fait, une roulette tournera et une couleur sera aléatoirement choisie ! Si la couleur choisie est celle sur laquelle vous avez pariée au début, vous doublez votre mise, sinon, vous perdez tout !\n\nNOTE : La couleur verte n'apparait qu'une fois dans les 37 numéros de la roulette, il est donc très rare de tomber sur cette couleur. Si la roulette sélectionne la couleur verte et que vous avez parié dessus, votre mise initiale sera multipliée par 14 !",
            "dragon-tower" => "Dans ce jeu, vous commencez au palier 0 ! Le but est de faire le bon choix parmi 4 blocks noirs ! Ils sont tous identiques, mais derrière l'un d'eux se cache un block vert. Les autres cachent tous des blocks rouges. Le but du jeu est de gravir les différents palliers et de trouver tous les blocks verts de chaque étage !",
            "mines" => "Votre mission est de révéler autant de blocks verts que possible, de manière séquentielle, sans trouver d'explosif (block TNT) par inadvertance. C'est le cœur du jeu, avec la possibilité d'encaisser vos gains à tout moment, surtout si vous sentez un risque imminent. Chaque supposition réussie, signifiée par le dévoilement d'un block vert, augmente votre multiplicateur, ce qui a pour effet d'amplifier vos gains !"
        };
    }
}