<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\faction;

use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\SimpleForm;
use NCore\handler\OtherAPI;
use NCore\handler\RankAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\item\Armor;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use Util\util\IdsUtils;

class Kit extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "kit",
            "Permet d'accèder au menu des kits"
        );
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->inCooldown("combat")) {
                $sender->sendMessage(Util::PREFIX . "Cette commande est interdite en combat");
                return;
            }

            $form = new SimpleForm(function (Player $player, mixed $data) use ($session) {
                if (!is_string($data)) {
                    return;
                }

                $kit = self::getKits()[$data];

                if (!RankApi::hasRank($player, $kit["rank"])) {
                    $player->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de prendre ce kit");
                    return;
                } else if ($session->inCooldown("kit_" . $data) && !$player->hasPermission("pocketmine.group.operator")) {
                    $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas prendre le kit " . $data . " maintenant, merci d'attendre: §6" . $this->getRemainingTime($player, "kit_" . $data));
                    return;
                }

                $session->setCooldown("kit_" . $data, $kit["cooldown"]);

                foreach ($kit["items"] as $item) {
                    if ($item instanceof Armor) {
                        if ($player->getArmorInventory()->getItem($item->getArmorSlot())->getId() === 0) {
                            $player->getArmorInventory()->setItem($item->getArmorSlot(), $item);
                            continue;
                        }
                    }

                    if ($item->getId() === ItemIds::NAUTILUS_SHELL && $player->getNetworkSession()->getPlayerInfo()->getExtraData()["CurrentInputMode"] !== 2) {
                        $item->setCount(0);
                    }

                    if ($item->getId() === ItemIds::ENDER_PEARL) {
                        $item->setCount(max(32 - OtherAPI::getItemCount($player, ItemIds::ENDER_PEARL), 0));
                    }

                    $player->getInventory()->addItem($item);
                }

                $player->sendMessage(Util::PREFIX . "Vous venez de recevoir votre kit !");
            });
            $form->setTitle("Kit");
            $form->setContent(Util::PREFIX . "Quel kit voulez-vous prendre");

            foreach (self::getKits() as $key => $value) {
                $name = ucfirst(strtolower($key));

                if (!RankAPI::hasRank($sender, $value["rank"])) {
                    $name .= "\n§cNon débloqué";
                } else if ($session->inCooldown("kit_" . $key) && !$sender->hasPermission("pocketmine.group.operator")) {
                    $name .= " §c(Cooldown)\n" . $this->getRemainingTime($sender, "kit_" . $key);
                }

                $form->addButton($name, -1, "", $key);
            }
            $sender->sendForm($form);
        }
    }

    public static function getKits(): array
    {
        $unbreaking = new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 3);
        $protection = new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 2);
        $efficiency = new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 5);
        $sharpness = new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 2);

        return [
            "refill" => [
                "items" => [
                    ItemFactory::getInstance()->get(ItemIds::NAUTILUS_SHELL),
                    ItemFactory::getInstance()->get(ItemIds::ENDER_PEARL, 0, 32),
                    ItemFactory::getInstance()->get(ItemIds::SPLASH_POTION, 22, 40)
                ],
                "cooldown" => 5,
                "rank" => "joueur"
            ],
            "mineur" => [
                "items" => [
                    ItemFactory::getInstance()->get(ItemIds::DIAMOND_PICKAXE)->addEnchantment($unbreaking)->addEnchantment($efficiency),
                    ItemFactory::getInstance()->get(ItemIds::DIAMOND_AXE)->addEnchantment($unbreaking)->addEnchantment($efficiency),
                    ItemFactory::getInstance()->get(ItemIds::DIAMOND_SHOVEL)->addEnchantment($unbreaking)->addEnchantment($efficiency),
                    ItemFactory::getInstance()->get(ItemIds::DIAMOND_HOE)->addEnchantment($unbreaking)
                ],
                "cooldown" => 60,
                "rank" => "joueur"
            ],
            "joueur" => [
                "items" => [
                    ItemFactory::getInstance()->get(ItemIds::DIAMOND_HELMET)->addEnchantment($unbreaking)->addEnchantment($protection),
                    ItemFactory::getInstance()->get(ItemIds::DIAMOND_CHESTPLATE)->addEnchantment($unbreaking)->addEnchantment($protection),
                    ItemFactory::getInstance()->get(ItemIds::DIAMOND_LEGGINGS)->addEnchantment($unbreaking)->addEnchantment($protection),
                    ItemFactory::getInstance()->get(ItemIds::DIAMOND_BOOTS)->addEnchantment($unbreaking)->addEnchantment($protection),
                    ItemFactory::getInstance()->get(ItemIds::DIAMOND_SWORD)->addEnchantment($sharpness)->addEnchantment($unbreaking),
                    ItemFactory::getInstance()->get(ItemIds::ENDER_PEARL, 0, 16)
                ],
                "cooldown" => 60,
                "rank" => "joueur"
            ],
            "champion" => [
                "items" => [
                    ItemFactory::getInstance()->get(IdsUtils::EMERALD_HELMET)->addEnchantment($unbreaking)->addEnchantment($protection),
                    ItemFactory::getInstance()->get(ItemIds::DIAMOND_CHESTPLATE)->addEnchantment($unbreaking)->addEnchantment($protection),
                    ItemFactory::getInstance()->get(ItemIds::DIAMOND_LEGGINGS)->addEnchantment($unbreaking)->addEnchantment($protection),
                    ItemFactory::getInstance()->get(ItemIds::DIAMOND_BOOTS)->addEnchantment($unbreaking)->addEnchantment($protection),
                    ItemFactory::getInstance()->get(ItemIds::DIAMOND_SWORD)->addEnchantment($sharpness)->addEnchantment($unbreaking),
                    ItemFactory::getInstance()->get(ItemIds::ENDER_PEARL, 0, 16),
                    ItemFactory::getInstance()->get(IdsUtils::STRENGTH_COOKIE, 0, 8),
                    ItemFactory::getInstance()->get(IdsUtils::REGENERATION_COOKIE, 0, 8),
                    ItemFactory::getInstance()->get(IdsUtils::SPEED_COOKIE, 0, 8)
                ],
                "cooldown" => 60 * 60,
                "rank" => "champion"
            ],
            "prince" => [
                "items" => [
                    ItemFactory::getInstance()->get(IdsUtils::EMERALD_HELMET)->addEnchantment($unbreaking)->addEnchantment($protection),
                    ItemFactory::getInstance()->get(ItemIds::DIAMOND_CHESTPLATE)->addEnchantment($unbreaking)->addEnchantment($protection),
                    ItemFactory::getInstance()->get(ItemIds::DIAMOND_LEGGINGS)->addEnchantment($unbreaking)->addEnchantment($protection),
                    ItemFactory::getInstance()->get(IdsUtils::EMERALD_BOOTS)->addEnchantment($unbreaking)->addEnchantment($protection),
                    ItemFactory::getInstance()->get(ItemIds::DIAMOND_SWORD)->addEnchantment($sharpness)->addEnchantment($unbreaking),
                    ItemFactory::getInstance()->get(ItemIds::ENDER_PEARL, 0, 16),
                    ItemFactory::getInstance()->get(IdsUtils::STRENGTH_COOKIE, 0, 16),
                    ItemFactory::getInstance()->get(IdsUtils::REGENERATION_COOKIE, 0, 16),
                    ItemFactory::getInstance()->get(IdsUtils::SPEED_COOKIE, 0, 16)
                ],
                "cooldown" => 60 * 60 * 2,
                "rank" => "prince"
            ],
            "elite" => [
                "items" => [
                    ItemFactory::getInstance()->get(IdsUtils::EMERALD_HELMET)->addEnchantment($unbreaking)->addEnchantment($protection),
                    ItemFactory::getInstance()->get(IdsUtils::EMERALD_CHESTPLATE)->addEnchantment($unbreaking)->addEnchantment($protection),
                    ItemFactory::getInstance()->get(IdsUtils::EMERALD_LEGGINGS)->addEnchantment($unbreaking)->addEnchantment($protection),
                    ItemFactory::getInstance()->get(IdsUtils::EMERALD_BOOTS)->addEnchantment($unbreaking)->addEnchantment($protection),
                    ItemFactory::getInstance()->get(ItemIds::DIAMOND_SWORD)->addEnchantment($sharpness)->addEnchantment($unbreaking),
                    ItemFactory::getInstance()->get(ItemIds::ENDER_PEARL, 0, 16),
                    ItemFactory::getInstance()->get(IdsUtils::COMBINED_COOKIE, 0, 8)
                ],
                "cooldown" => 60 * 60 * 3,
                "rank" => "elite"
            ],
            "roi" => [
                "items" => [
                    ItemFactory::getInstance()->get(IdsUtils::EMERALD_HELMET)->addEnchantment($unbreaking)->addEnchantment($protection),
                    ItemFactory::getInstance()->get(IdsUtils::EMERALD_CHESTPLATE)->addEnchantment($unbreaking)->addEnchantment($protection),
                    ItemFactory::getInstance()->get(IdsUtils::EMERALD_LEGGINGS)->addEnchantment($unbreaking)->addEnchantment($protection),
                    ItemFactory::getInstance()->get(IdsUtils::EMERALD_BOOTS)->addEnchantment($unbreaking)->addEnchantment($protection),
                    ItemFactory::getInstance()->get(IdsUtils::EMERALD_SWORD)->addEnchantment($sharpness)->addEnchantment($unbreaking),
                    ItemFactory::getInstance()->get(ItemIds::ENDER_PEARL, 0, 16),
                    ItemFactory::getInstance()->get(IdsUtils::COMBINED_COOKIE, 0, 16)
                ],
                "cooldown" => 60 * 60 * 4,
                "rank" => "roi"
            ]
        ];
    }

    private function getRemainingTime(Player $player, string $cooldown): string
    {
        $session = Session::get($player);
        $time = $session->getCooldownData($cooldown)[0] - time();

        $seconds = $time % 60;
        $minutes = floor(($time % 3600) / 60);
        $hours = floor(($time % 86400) / 3600);

        return $hours . " heure(s), " . $minutes . " minute(s), " . $seconds . " seconde(s)";
    }

    protected function prepare(): void
    {
    }
}