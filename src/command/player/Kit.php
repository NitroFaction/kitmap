<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player;

use CortexPE\Commando\BaseCommand;
use Element\item\ExtraVanillaItems;
use jojoe77777\FormAPI\SimpleForm;
use Kitmap\handler\Rank;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\item\Armor;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\PotionType;
use pocketmine\item\VanillaItems;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Kit extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "kit",
            "Permet d'accèder au menu des kits"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
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

                if (!Rank::hasRank($player, $kit["rank"])) {
                    $player->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de prendre ce kit");
                    return;
                } else if ($session->inCooldown("kit_" . $data) && !$player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
                    $format = Util::formatDurationFromSeconds($session->getCooldownData("kit_" . $data)[0] - time(), 1);
                    $player->sendMessage(Util::PREFIX . "Vous ne pourrez re-prendre le kit §q" . $data . " §fque dans: §q" . $format);
                    return;
                }

                $session->setCooldown("kit_" . $data, $kit["cooldown"]);

                foreach ($kit["items"] as $item) {
                    if ($item instanceof Armor) {
                        if ($player->getArmorInventory()->getItem($item->getArmorSlot())->equals(VanillaItems::AIR())) {
                            $player->getArmorInventory()->setItem($item->getArmorSlot(), $item);
                            continue;
                        }
                    }

                    if ($item->equals(VanillaItems::NAUTILUS_SHELL()) && $player->getNetworkSession()->getPlayerInfo()->getExtraData()["CurrentInputMode"] !== 2) {
                        $item->setCount(0);
                    }

                    if ($item->equals(VanillaItems::ENDER_PEARL())) {
                        $item->setCount(max(32 - Util::getItemCount($player, VanillaItems::ENDER_PEARL()), 0));
                    }

                    $player->getInventory()->addItem($item);
                }

                $player->sendMessage(Util::PREFIX . "Vous venez de recevoir votre kit !");
            });
            $form->setTitle("Kit");
            $form->setContent(Util::PREFIX . "Quel kit voulez-vous prendre");

            foreach (self::getKits() as $key => $value) {
                $name = ucfirst(strtolower($key));

                if (!Rank::hasRank($sender, $value["rank"])) {
                    $name .= "\n§cNon débloqué";
                } else if ($session->inCooldown("kit_" . $key) && !$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
                    $format = Util::formatDurationFromSeconds($session->getCooldownData("kit_" . $key)[0] - time(), 1);
                    $name .= " §c(Cooldown)\n" . $format;
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
                    VanillaItems::NAUTILUS_SHELL(),
                    VanillaItems::ENDER_PEARL()->setCount(32),
                    VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_HEALING())->setCount(40)
                ],
                "cooldown" => 5,
                "rank" => "joueur"
            ],
            "mineur" => [
                "items" => [
                    VanillaItems::DIAMOND_PICKAXE()->addEnchantment($unbreaking)->addEnchantment($efficiency),
                    VanillaItems::DIAMOND_AXE()->addEnchantment($unbreaking)->addEnchantment($efficiency),
                    VanillaItems::DIAMOND_SHOVEL()->addEnchantment($unbreaking)->addEnchantment($efficiency),
                    VanillaItems::DIAMOND_HOE()->addEnchantment($unbreaking)
                ],
                "cooldown" => 60,
                "rank" => "joueur"
            ],
            "joueur" => [
                "items" => [
                    VanillaItems::NETHERITE_HELMET()->addEnchantment($unbreaking)->addEnchantment($protection),
                    VanillaItems::DIAMOND_CHESTPLATE()->addEnchantment($unbreaking)->addEnchantment($protection),
                    VanillaItems::DIAMOND_LEGGINGS()->addEnchantment($unbreaking)->addEnchantment($protection),
                    VanillaItems::NETHERITE_BOOTS()->addEnchantment($unbreaking)->addEnchantment($protection),
                    VanillaItems::DIAMOND_SWORD()->addEnchantment($sharpness)->addEnchantment($unbreaking)
                ],
                "cooldown" => 60,
                "rank" => "joueur"
            ],
            "champion" => [
                "items" => [
                    VanillaItems::NETHERITE_HELMET()->addEnchantment($unbreaking)->addEnchantment($protection),
                    VanillaItems::DIAMOND_CHESTPLATE()->addEnchantment($unbreaking)->addEnchantment($protection),
                    VanillaItems::NETHERITE_LEGGINGS()->addEnchantment($unbreaking)->addEnchantment($protection),
                    VanillaItems::NETHERITE_BOOTS()->addEnchantment($unbreaking)->addEnchantment($protection),
                    VanillaItems::NETHERITE_SWORD()->addEnchantment($sharpness)->addEnchantment($unbreaking)
                ],
                "cooldown" => 60 * 60,
                "rank" => "champion"
            ],
            "prince" => [
                "items" => [
                    VanillaItems::NETHERITE_HELMET()->addEnchantment($unbreaking)->addEnchantment($protection),
                    VanillaItems::NETHERITE_CHESTPLATE()->addEnchantment($unbreaking)->addEnchantment($protection),
                    VanillaItems::NETHERITE_LEGGINGS()->addEnchantment($unbreaking)->addEnchantment($protection),
                    VanillaItems::NETHERITE_BOOTS()->addEnchantment($unbreaking)->addEnchantment($protection),
                    VanillaItems::NETHERITE_SWORD()->addEnchantment($sharpness)->addEnchantment($unbreaking)
                ],
                "cooldown" => 60 * 60 * 2,
                "rank" => "prince"
            ],
            "elite" => [
                "items" => [
                    ExtraVanillaItems::EMERALD_HELMET()->addEnchantment($unbreaking)->addEnchantment($protection),
                    VanillaItems::NETHERITE_CHESTPLATE()->addEnchantment($unbreaking)->addEnchantment($protection),
                    VanillaItems::NETHERITE_LEGGINGS()->addEnchantment($unbreaking)->addEnchantment($protection),
                    VanillaItems::NETHERITE_BOOTS()->addEnchantment($unbreaking)->addEnchantment($protection),
                    ExtraVanillaItems::EMERALD_SWORD()->addEnchantment($sharpness)->addEnchantment($unbreaking)
                ],
                "cooldown" => 60 * 60 * 3,
                "rank" => "elite"
            ],
            "roi" => [
                "items" => [
                    ExtraVanillaItems::EMERALD_HELMET()->addEnchantment($unbreaking)->addEnchantment($protection),
                    VanillaItems::NETHERITE_CHESTPLATE()->addEnchantment($unbreaking)->addEnchantment($protection),
                    VanillaItems::NETHERITE_LEGGINGS()->addEnchantment($unbreaking)->addEnchantment($protection),
                    ExtraVanillaItems::EMERALD_BOOTS()->addEnchantment($unbreaking)->addEnchantment($protection),
                    ExtraVanillaItems::EMERALD_SWORD()->addEnchantment($sharpness)->addEnchantment($unbreaking)
                ],
                "cooldown" => 60 * 60 * 4,
                "rank" => "roi"
            ]
        ];
    }

    protected function prepare(): void
    {
    }
}