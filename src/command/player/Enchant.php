<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player;

use CortexPE\Commando\args\BooleanArgument;
use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use Kitmap\handler\OtherAPI;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\data\bedrock\EnchantmentIds;
use pocketmine\item\Armor;
use pocketmine\item\Axe;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\Pickaxe;
use pocketmine\item\Shovel;
use pocketmine\item\Sword as PmSword;
use pocketmine\item\TieredTool;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use Util\item\items\custom\Sword;

class Enchant extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "enchant",
            "Ouvre un table d'enchantement pour améliorer l'item dans votre main"
        );

        $this->setPermission("pocketmine.group.operator");
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

        if (!$item instanceof Armor && !$item instanceof TieredTool && !$item instanceof Sword) {
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
        $form->setContent(Util::PREFIX . "Cliquez sur le boutton de votre choix");

        if ($item instanceof PmSword || $item instanceof Sword) $form->addButton("Tranchant", -1, "", EnchantmentIds::SHARPNESS . ";Tranchant;2");
        if ($item instanceof Armor) $form->addButton("Protection", -1, "", EnchantmentIds::PROTECTION . ";Protection;2");
        if (($item instanceof Pickaxe) || $item instanceof Axe || $item instanceof Shovel) $form->addButton("Efficacité", -1, "", EnchantmentIds::EFFICIENCY . ";Efficacité;5");

        $form->addButton("Solidité", -1, "", EnchantmentIds::UNBREAKING . ";Solidité;3");
        $player->sendForm($form);
    }

    public static function openEnchantLevelsMenu(Player $player, string $data, bool $force): void
    {
        list($enchantId, $enchantName, $maxLevel) = explode(";", $data);
        $x = 1;

        $form = new SimpleForm(function (Player $player, mixed $data) use ($enchantId, $force) {
            if (!is_int($data)) {
                return;
            }

            self::confirmationForm($player, $enchantId, $data + 1, $force);
        });
        $form->setTitle("Enchantement");
        $form->setContent(Util::PREFIX . "Cliquez sur le boutton de votre choix");

        while ($x <= $maxLevel) {
            $form->addButton($enchantName . " " . $x . "\n§e" . ($x * 10) . " levels §8ou §e" . ($x * 10) . " émeraudes");
            $x++;
        }
        $player->sendForm($form);
    }

    private static function confirmationForm(Player $player, int $enchantId, int $enchantLevel, bool $force): void
    {
        if ($force) {
            self::enchantItem($player, $enchantId, $enchantLevel);
            return;
        }

        $form = new CustomForm(function (Player $player, mixed $data) use ($enchantId, $enchantLevel) {
            if (!is_array($data) || !isset($data[1]) || !isset($data[2]) || !is_bool($data[2]) || !$data[2]) {
                return;
            }

            switch ($data[1]) {
                case 0:
                    if (($enchantLevel * 10) > $player->getXpManager()->getXpLevel()) {
                        $player->sendMessage(Util::PREFIX . "Vous n'avez pas assez de niveaux pour enchanter votre item");
                        return;
                    }

                    $player->getXpManager()->setXpLevel($player->getXpManager()->getXpLevel() - ($enchantLevel * 10));
                    break;
                case 1:
                    if (($enchantLevel * 10) > OtherAPI::getItemCount($player, ItemIds::EMERALD)) {
                        $player->sendMessage(Util::PREFIX . "Vous n'avez pas assez d'émeraudes pour enchanter votre item");
                        return;
                    }

                    $player->getInventory()->removeItem(ItemFactory::getInstance()->get(ItemIds::EMERALD, 0, ($enchantLevel * 10)));
                    break;
                default:
                    return;
            }

            self::enchantItem($player, $enchantId, $enchantLevel);
        });
        $form->setTitle("Enchantement");
        $form->addLabel(Util::PREFIX . "Êtes vous sur d'enchanter l'item dans votre main ?");
        $form->addDropdown("Méthode de payement", ["levels", "émeraudes"]);
        $form->addToggle("Enchanter votre item?", true);
        $player->sendForm($form);
    }

    private static function enchantItem(Player $player, int $enchantId, int $enchantLevel): void
    {
        $item = $player->getInventory()->getItemInHand();

        if (!$item instanceof Armor && !$item instanceof TieredTool && !$item instanceof Sword) {
            $player->sendMessage(Util::PREFIX . "L'item dans votre main n'est pas enchantable");
            return;
        }

        $enchant = new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId($enchantId), $enchantLevel);
        $item->addEnchantment($enchant);

        if (!is_null($item->getNamedTag()->getTag("update"))) {
            $item->getNamedTag()->removeTag("update");
        }

        $player->getInventory()->setItemInHand($item);
        $player->sendMessage(Util::PREFIX . "L'item dans votre main a été enchanté");
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new BooleanArgument("force", true));
    }
}