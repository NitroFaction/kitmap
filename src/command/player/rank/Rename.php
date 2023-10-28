<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player\rank;

use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\CustomForm;
use Kitmap\enchantment\EnchantmentIds;
use Kitmap\handler\Rank;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\VanillaItems;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Rename extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "rename",
            "Renomme les items"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if (!Rank::hasRank($sender, "prince")) {
                $sender->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de faire cela");
                return;
            }

            if ($session->inCooldown("rename")) {
                $format = Util::formatDurationFromSeconds($session->getCooldownData("rename")[0] - time());
                $sender->sendMessage(Util::PREFIX . "Vous ne pourrez ré-utiliser la commande §6/rename §fque dans: §6" . $format);
                return;
            }

            $form = new CustomForm(function (Player $player, mixed $data) use ($session) {
                if (!is_array($data) || !isset($data[0]) || 1 > strlen($data[0])) {
                    return;
                }

                $item = $player->getInventory()->getItemInHand();

                if ($item->hasEnchantment(EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::ARES))) {
                    $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas renommer un item possédant l'enchantement Arès");
                    return;
                } else if ($item->equals(VanillaItems::AIR()) || $item->getTypeId() === VanillaItems::PAPER()->getTypeId() || $item->getTypeId() === VanillaItems::EXPERIENCE_BOTTLE()->getTypeId() || !is_null($item->getNamedTag()->getTag("partneritem")) || !is_null($item->getNamedTag()->getTag("type")) || !is_null($item->getNamedTag()->getTag("data"))) {
                    $player->sendMessage(Util::PREFIX . "L'item dans votre main ne peut pas être renommé");
                    return;
                }

                $customName = "§r§f" . $data[0];
                $item->setCustomName($customName);

                $player->getInventory()->setItemInHand($item);
                $session->setCooldown("rename", (60 * 3));

                $player->sendMessage(Util::PREFIX . "Vous venez de renommer l'item dans votre main en " . $data[0]);
                Main::getInstance()->getLogger()->info("Le joueur " . $player->getName() . " vient de renommer l'item dans sa main en " . $data[0]);
            });
            $form->setTitle("Rename");
            $form->addInput(Util::PREFIX . "Tapez un nom personnalisé dans le champ ci-dessous, vous pouvez utiliser les couleurs");
            $sender->sendForm($form);
        }
    }

    protected function prepare(): void
    {
    }
}
