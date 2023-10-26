<?php /* @noinspection PhpUnused */

namespace Kitmap\command\player;

use CortexPE\Commando\args\BooleanArgument;
use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use Kitmap\enchantment\EnchantmentIds as CustomEnchantmentIds;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\data\bedrock\EnchantmentIds;
use pocketmine\item\Armor;
use pocketmine\item\Axe;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Pickaxe;
use pocketmine\item\Shovel;
use pocketmine\item\Sword;
use pocketmine\item\TieredTool;
use pocketmine\item\VanillaItems;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Enchant extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "enchant",
            "Ouvre un table d'enchantement pour améliorer l'item dans votre main"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $force = $args["force"] ?? false;
            self::openEnchantTable($sender, $force);
        }
    }

    public static function openEnchantTable(Player $player, bool $force): void
    {
        $item = $player->getInventory()->getItemInHand();

        if (!$item instanceof Armor && !$item instanceof TieredTool) {
            $player->sendMessage(Util::PREFIX . "L'item dans votre main n'est pas enchantable");
            return;
        }

        $form = new SimpleForm(function (Player $player, mixed $data) use ($force) {
            if (!is_string($data)) {
                return;
            }

            self::openEnchantLevelsMenu($player, $data, $force);
        });

        $form->setTitle("Enchantement");
        $form->setContent(Util::PREFIX . "Cliquez sur l'enchantement de votre choix");

        if ($item instanceof Sword) {
            $form->addButton("Tranchant", label: EnchantmentIds::SHARPNESS . ";Tranchant;2");
<<<<<<< Updated upstream
            $form->addButton("Pilleur", label: CustomEnchantmentIds::LOOTER . ";Pilleur;3");
            $form->addButton("Foudroiement", label: CustomEnchantmentIds::LIGHTNING_STRIKE . ";Foudroiement;3");
            $form->addButton("Arès", label: CustomEnchantmentIds::ARES . ";Arès;1");
=======
>>>>>>> Stashed changes
        } else if ($item instanceof Armor) {
            $form->addButton("Protection", label: EnchantmentIds::PROTECTION . ";Protection;2");
        } else if ($item instanceof Pickaxe || $item instanceof Axe || $item instanceof Shovel) {
            $form->addButton("Efficacité", label: EnchantmentIds::EFFICIENCY . ";Efficacité;5");
        }

        $form->addButton("Solidité", label: EnchantmentIds::UNBREAKING . ";Solidité;3");

        if ($item instanceof Sword) {
            $form->addButton("Pilleur", label: CustomEnchantmentIds::LOOTER . ";Pilleur;3");
            $form->addButton("Foudroiement", label: CustomEnchantmentIds::LIGHTNING_STRIKE . ";Foudroiement;3");
            $form->addButton("Arès", label: CustomEnchantmentIds::ARES . ";Arès;1");
        }

        $player->sendForm($form);
    }

    public static function openEnchantLevelsMenu(Player $player, string $data, bool $force): void
    {
        [$enchantId, $enchantName, $maxLevel] = explode(";", $data);
        $x = 1;

        $multiplier = match (intval($enchantId)) {
            CustomEnchantmentIds::LOOTER => 2,
            CustomEnchantmentIds::LIGHTNING_STRIKE => 3,
            CustomEnchantmentIds::ARES => 5,
            default => 1
        };

        $onlyLevels = $enchantId > 1000;

        $form = new SimpleForm(function (Player $player, mixed $data) use ($enchantId, $multiplier, $onlyLevels, $force) {
            if (!is_int($data)) {
                return;
            }

            self::confirmationForm($player, $enchantId, $data + 1, $multiplier, $onlyLevels, $force);
        });

        $form->setTitle("Enchantement");
        $form->setContent(Util::PREFIX . "Cliquez sur le boutton de votre choix");

        while ($x <= $maxLevel) {
            $price = (($x * 10) * $multiplier);
            $content = $onlyLevels
                ? $enchantName . " " . $x . "\n§6" . $price . " levels"
                : $enchantName . " " . $x . "\n§6" . $price . " levels §8ou §6" . $price . " émeraudes";
            $form->addButton($content);
            $x++;
        }

        $player->sendForm($form);
    }

    private static function confirmationForm(Player $player, int $enchantId, int $enchantLevel, int $multiplier, bool $onlyLevels, bool $force): void
    {
        if ($force) {
            self::enchantItem($player, $enchantId, $enchantLevel, 0, "levels");
            return;
        }

        $form = new CustomForm(function (Player $player, mixed $data) use ($enchantId, $enchantLevel, $multiplier) {
            if (!is_array($data) || !isset($data[1]) || !isset($data[2]) || !is_bool($data[2]) || !$data[2]) {
                return;
            }

            switch ($data[1]) {
                case 0:
                    $finalPrice = ($enchantLevel * 10) * $multiplier;
                    if ($finalPrice > $player->getXpManager()->getXpLevel()) {
                        $player->sendMessage(Util::PREFIX . "Vous n'avez pas assez de niveaux pour enchanter votre item");
                        return;
                    }
                    break;
                case 1:
                    $finalPrice = ($enchantLevel * 10) * $multiplier;
                    if ($finalPrice > Util::getItemCount($player, VanillaItems::EMERALD())) {
                        $player->sendMessage(Util::PREFIX . "Vous n'avez pas assez d'émeraudes pour enchanter votre item");
                        return;
                    }
                    break;
                default:
                    return;
            }

            $type = $data[1] === 0 ? "levels" : "émeraudes";
            self::enchantItem($player, $enchantId, $enchantLevel, $finalPrice, $type);
        });
        $form->setTitle("Enchantement");
        $form->addLabel(Util::PREFIX . "Êtes-vous sur d'enchanter l'item dans votre main ?");
        $form->addDropdown("Méthode de paiement", $onlyLevels ? ["levels"] : ["levels", "émeraudes"]);
        $form->addToggle("Enchanter votre item ?", true);
        $player->sendForm($form);
    }

    private static function enchantItem(Player $player, int $enchantId, int $enchantLevel, int $price, string $type): void
    {
        $item = $player->getInventory()->getItemInHand();

        if (!$item instanceof Armor && !$item instanceof TieredTool) {
            $player->sendMessage(Util::PREFIX . "L'item dans votre main n'est pas enchantable");
            return;
        }

        $enchant = EnchantmentIdMap::getInstance()->fromId($enchantId);
        $enchantInstance = new EnchantmentInstance($enchant, $enchantLevel);

        $enchantName = $enchantInstance->getType()->getName();
        $formattedEnchant = "§r§7" . $enchantName . " " . Util::formatToRomanNumber($enchantInstance->getLevel());

        if ($item->hasEnchantment($enchant)) {
            $player->sendMessage(Util::PREFIX . "Votre item possède déjà cet enchantement.");
            return;
        }

        $item->addEnchantment($enchantInstance);

<<<<<<< Updated upstream
        if (is_string($enchantName)) {
            $lore = $item->getLore();
            $lore[] = $formattedEnchant;
            $item->setLore($lore);
        }

        if ($type == "levels") {
            $player->getXpManager()->setXpLevel($player->getXpManager()->getXpLevel() - $price);
        } else if ($type == "émeraudes") {
            $player->getInventory()->removeItem(VanillaItems::EMERALD()->setCount($price));
        }

=======
>>>>>>> Stashed changes
        $player->getInventory()->setItemInHand($item);
        $player->sendMessage(Util::PREFIX . "L'item dans votre main a été enchanté");
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new BooleanArgument("force", true));
    }
}
