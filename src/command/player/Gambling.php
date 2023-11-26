<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use Kitmap\handler\Faction as FactionAPI;
use Kitmap\handler\PartnerItems;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\task\repeat\GamblingTask;
use Kitmap\Util;
use MaXoooZ\Util\item\ExtraVanillaItems;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\command\CommandSender;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\PotionType;
use pocketmine\item\VanillaItems;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Gambling extends BaseCommand
{
    public static array $gamblings = [];

    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "gambling",
            "Permet de proposer un gambling à un joueur "
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $option = $args["opt"] ?? null;

        if ($option === "stop" && $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            if (GamblingTask::$currently) {
                GamblingTask::stop();
            } else {
                $sender->sendMessage(Util::PREFIX . "Aucun gambling n'est actuellement en cours");
            }
            return;
        }

        if ($sender instanceof Player) {
            if (!Util::insideZone($sender->getPosition(), "spawn")) {
                $sender->sendMessage(Util::PREFIX . "Vous devez être dans le spawn pour pouvoir accèder au menu de gambling");
                return;
            } else if (Session::get($sender)->data["staff_mod"][0]) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas accèder au menu de gambling en staffmod");
                return;
            }

            $form = new SimpleForm(function (Player $player, mixed $data) {
                if (!is_int($data)) {
                    return;
                }

                switch ($data) {
                    case 0:
                        $this->createForm($player);
                        break;
                    case 1:
                        $this->showForm($player);
                        break;
                    case 2:
                        $this->showKits($player);
                        break;
                }
            });
            $form->setTitle("Gambling");
            $form->setContent(Util::PREFIX . "Cliquez sur le boutton de votre choix");
            $form->addButton("Créer");
            $form->addButton("Rejoindre §6(" . count(self::$gamblings) . ")");
            $form->addButton("Visualiser les kits");
            $sender->sendForm($form);
        }
    }

    private function createForm(Player $player): void
    {
        $form = new CustomForm(function (Player $player, mixed $data) {
            if (!is_array($data) || !isset($data["bet"]) || !isset($data["kit"])) {
                return;
            }

            $session = Session::get($player);

            if ($session->inCooldown("gambling")) {
                $player->sendMessage(Util::PREFIX . "Vous devez encore attendre §6" . Util::formatDurationFromSeconds($session->getCooldownData("gambling")[0] - time()) . " §favant de pouvoir re-créer un gambling");
                return;
            }

            $bet = intval($data["bet"]);
            $kit = intval($data["kit"]);

            $lowerName = strtolower($player->getName());

            if ($bet > $session->data["money"]) {
                $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas miser plus que votre monnaie actuel");
                return;
            } else if ($bet < 0) {
            	$player->sendMessage(Util::PREFIX . "Vous ne pouvez pas miser cette somme");
                return;
            } else if (isset(self::$gamblings[$lowerName])) {
                $player->sendMessage(Util::PREFIX . "Vous avez déjà un gambling en attente");
                return;
            }

            self::$gamblings[$lowerName] = [
                "bet" => $bet,
                "kit" => $kit,
                "upper_name" => $player->getName()
            ];

            $session->setCooldown("gambling", 60 * 5);
            $session->addValue("money", $bet, true);

            Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le joueur §6" . $player->getName() . " §fvient de créer un gambling ! Affrontez le via la commande §6/gambling §f!");
        });
        $form->setTitle("Gambling");
        $form->addInput(Util::PREFIX . "Combien misez vous sur votre victoire?", default: 0, label: "bet");
        $form->addDropdown(Util::PREFIX . "Avec quel kit voulez vous vous battre?", ["Kit 1", "Kit 2", "Kit 3", "Kit 4"], 0, "kit");
        $player->sendForm($form);
    }

    private function showForm(Player $player): void
    {
        $form = new SimpleForm(function (Player $player, mixed $data) {
            if (!is_string($data)) {
                return;
            } else if ($data === "refresh") {
                $this->showForm($player);
                return;
            }

            $this->confirmationForm($player, $data);
        });
        $form->setTitle("Gambling");
        $form->setContent(Util::PREFIX . "Cliquez sur le boutton de votre choix");

        foreach (self::$gamblings as $target => $value) {
            $form->addButton($value["upper_name"] . "\nMise de §6" . Util::formatNumberWithSuffix($value["bet"], 1) . " pièces §8- Kit §6" . $value["kit"] + 1, -1, "", $target);
        }

        $form->addButton("Rafraîchir", -1, "", "refresh");
        $player->sendForm($form);
    }

    private function confirmationForm(Player $player, string $target): void
    {
        if (strtolower($player->getName()) === $target) {
            $this->deleteForm($player);
            return;
        }

        $form = new SimpleForm(function (Player $player, mixed $data) use ($target) {
            if (!is_string($data) || $data != "yes") {
                return;
            }

            $p = Main::getInstance()->getServer()->getPlayerExact($target);

            if (!isset(self::$gamblings[$target])) {
                $player->sendMessage(Util::PREFIX . "Ce gambling en attente n'est plus disponible");
                return;
            } else {
                if (!$p instanceof Player || !$p->isOnline() || !Util::insideZone($p->getPosition(), "spawn")) {
                    $player->sendMessage(Util::PREFIX . "Ce gambling en attente n'est plus disponible");
                    unset(self::$gamblings[$target]);
                    return;
                }
            }

            $data = self::$gamblings[$target];
            $bet = $data["bet"];

            $session = Session::get($player);

            if ($bet > $session->data["money"]) {
                $player->sendMessage(Util::PREFIX . "Vous n'avez pas assez de pièces pour rejoindre ce gambling");
                return;
            } else if ($p->getName() === $player->getName()) {
                $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas vous affronter vous même");
                return;
            } else if (0 > $bet) {
                $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas miser un nombre négatif");
                return;
            } else if (FactionAPI::hasFaction($player) && ($session->data["faction"] === Session::get($p)->data["faction"])) {
                $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas affronter des joueurs de votre faction en gambling");
                return;
            } else if (GamblingTask::$currently) {
                $player->sendMessage(Util::PREFIX . "Un gambling est déjà en cours, attendez sa fin pour affronter un joueur");
                return;
            }

            unset(self::$gamblings[$target]);
            $session->addValue("money", $bet, true);

            GamblingTask::$players = [
                $p->getName(),
                $player->getName()
            ];

            GamblingTask::$saves = [
                $p->getName() => Util::savePlayerData($p),
                $player->getName() => Util::savePlayerData($player)
            ];

            GamblingTask::$settings = $data;

            GamblingTask::$currently = true;
            GamblingTask::$since = -5;

            GamblingTask::$player1 = $p;
            GamblingTask::$player2 = $player;
        });
        $form->setTitle("Gambling");
        $form->setContent(Util::PREFIX . "Êtes vous sur de rejoindre ce gambling ?");
        $form->addButton("Oui", -1, "", "yes");
        $form->addButton("Non", -1, "", "no");
        $player->sendForm($form);
    }

    private function deleteForm(Player $player): void
    {
        $form = new SimpleForm(function (Player $player, mixed $data) {
            if (!is_string($data) || $data != "yes") {
                return;
            } else if (!isset(self::$gamblings[strtolower($player->getName())])) {
                $player->sendMessage(Util::PREFIX . "Vous n'avez pas de gambling en attente");
                return;
            }

            $data = self::$gamblings[strtolower($player->getName())];

            $session = Session::get($player);

            $session->removeCooldown("gambling");
            $session->addValue("money", $data["bet"]);

            unset(self::$gamblings[strtolower($player->getName())]);

            $player->sendMessage(Util::PREFIX . "Vous venez de retirer votre gambling en attente");
        });
        $form->setTitle("Gambling");
        $form->setContent(Util::PREFIX . "Êtes vous sur de supprimer votre gambling ?");
        $form->addButton("Oui", -1, "", "yes");
        $form->addButton("Non", -1, "", "no");
        $player->sendForm($form);
    }

    private function showKits(Player $player): void
    {
        $form = new SimpleForm(function (Player $player, mixed $data) {
            if (is_int($data)) {
                $menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);

                $menu->setName("Kit " . $data + 1);
                $menu->setListener(InvMenu::readonly());

                foreach (self::getKit($data) as $item) {
                    $menu->getInventory()->addItem($item);
                }

                $menu->send($player);
            }
        });
        $form->setTitle("Gambling");
        $form->setContent(Util::PREFIX . "Cliquez sur le boutton de votre choix");
        $form->addButton("Kit 1");
        $form->addButton("Kit 2");
        $form->addButton("Kit 3");
        $form->addButton("Kit 4");
        $player->sendForm($form);
    }

    public static function getKit(int $number): array
    {
        $unbreaking = new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 3);
        $protection = new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 2);
        $sharpness = new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 2);

        $kits = [
            [
                ExtraVanillaItems::EMERALD_HELMET()->addEnchantment($unbreaking)->addEnchantment($protection),
                ExtraVanillaItems::EMERALD_CHESTPLATE()->addEnchantment($unbreaking)->addEnchantment($protection),
                ExtraVanillaItems::EMERALD_LEGGINGS()->addEnchantment($unbreaking)->addEnchantment($protection),
                ExtraVanillaItems::EMERALD_BOOTS()->addEnchantment($unbreaking)->addEnchantment($protection),
                ExtraVanillaItems::IRIS_SWORD()->addEnchantment($sharpness)->addEnchantment($unbreaking),
                VanillaItems::ENDER_PEARL()->setCount(16),
                VanillaItems::RAW_FISH()->setCount(16),
                VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_HEALING())->setCount(6)
            ],
            [
                ExtraVanillaItems::EMERALD_HELMET()->addEnchantment($unbreaking)->addEnchantment($protection),
                ExtraVanillaItems::EMERALD_CHESTPLATE()->addEnchantment($unbreaking)->addEnchantment($protection),
                ExtraVanillaItems::EMERALD_LEGGINGS()->addEnchantment($unbreaking)->addEnchantment($protection),
                ExtraVanillaItems::EMERALD_BOOTS()->addEnchantment($unbreaking)->addEnchantment($protection),
                ExtraVanillaItems::IRIS_SWORD()->addEnchantment($sharpness)->addEnchantment($unbreaking),
                VanillaItems::ENDER_PEARL()->setCount(16),
                VanillaItems::RAW_FISH()->setCount(16),
                VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_HEALING())->setCount(15)
            ],
            [
                ExtraVanillaItems::IRIS_HELMET()->addEnchantment($unbreaking)->addEnchantment($protection),
                ExtraVanillaItems::IRIS_CHESTPLATE()->addEnchantment($unbreaking)->addEnchantment($protection),
                ExtraVanillaItems::IRIS_LEGGINGS()->addEnchantment($unbreaking)->addEnchantment($protection),
                ExtraVanillaItems::IRIS_BOOTS()->addEnchantment($unbreaking)->addEnchantment($protection),
                ExtraVanillaItems::IRIS_SWORD()->addEnchantment($sharpness)->addEnchantment($unbreaking),
                VanillaItems::ENDER_PEARL()->setCount(16),
                VanillaItems::RAW_FISH()->setCount(16),
                VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_HEALING())->setCount(15)
            ],
            [
                ExtraVanillaItems::IRIS_HELMET()->addEnchantment($unbreaking)->addEnchantment($protection),
                ExtraVanillaItems::IRIS_CHESTPLATE()->addEnchantment($unbreaking)->addEnchantment($protection),
                ExtraVanillaItems::IRIS_LEGGINGS()->addEnchantment($unbreaking)->addEnchantment($protection),
                ExtraVanillaItems::IRIS_BOOTS()->addEnchantment($unbreaking)->addEnchantment($protection),
                ExtraVanillaItems::IRIS_SWORD()->addEnchantment($sharpness)->addEnchantment($unbreaking),
                VanillaItems::ENDER_PEARL()->setCount(16),
                VanillaItems::RAW_FISH()->setCount(16),
                PartnerItems::createItem("focusmode")->setCount(4),
                PartnerItems::createItem("pumpkinaxe"),
                PartnerItems::createItem("strength")->setCount(4),
                VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_HEALING())->setCount(15)
            ]
        ];

        return $kits[$number];
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("opt", ["stop"], true));
    }
}