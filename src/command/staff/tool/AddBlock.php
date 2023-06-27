<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\tool;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use Util\item\items\custom\Pickaxe;

class AddBlock extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "addblock",
            "Ajoute des blocs minés à une pioche en ilvaïte"
        );

        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $item = $sender->getInventory()->getItemInHand();
            $amount = $args["blocs"];

            if (0 >= $amount) {
                $sender->sendMessage(Util::PREFIX . "Le montant que vous avez inscrit est invalide");
                return;
            }

            if ($item instanceof Pickaxe) {
                $blocks = is_null($item->getNamedTag()->getTag("blocks")) ? 0 : $item->getNamedTag()->getInt("blocks");

                $item->getNamedTag()->setInt("blocks", $blocks + ($amount - 1));
                $item->addBlockToCounter();

                $sender->getInventory()->setItemInHand($item);
                $sender->sendMessage(Util::PREFIX . "Vous venez d'ajouter §e" . $amount . " §fblocks minés à votre pioche en ilvaïte");
            } else {
                $sender->sendMessage(Util::PREFIX . "Vous devez avoir dans votre main une pioche en ilvaïte");
            }
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new IntegerArgument("blocs"));
    }
}