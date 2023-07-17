<?php /** @noinspection PhpUnused */

namespace NCore\command\staff\tool;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\args\TargetArgument;
use CortexPE\Commando\BaseCommand;
use NCore\Base;
use NCore\command\player\util\faction\Kit;
use NCore\handler\OtherAPI;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\item\Armor;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class GiveKit extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "givekit",
            "Donne un kit à un joueur"
        );

        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($args["joueur"] === "@a") {
            Util::allSelectorExecute($sender, $this->getName(), $args);
            return;
        }

        $target = Base::getInstance()->getServer()->getPlayerByPrefix($args["joueur"]);
        $items = Kit::getKits()[$args["kit"]]["items"];

        if (!$target instanceof Player) {
            $sender->sendMessage(Util::PREFIX . "Le joueur indiqué n'est pas connecté sur le serveur");
            return;
        }

        foreach ($items as $item) {
            if ($item instanceof Armor) {
                if ($target->getArmorInventory()->getItem($item->getArmorSlot())->getId() === 0) {
                    $target->getArmorInventory()->setItem($item->getArmorSlot(), $item);
                    continue;
                }
            }

            if ($target->getInventory()->canAddItem($item)) {
                OtherAPI::addItem($target, $item);
            } else {
                $target->getWorld()->dropItem($target->getPosition()->asVector3(), $item);
            }
        }

        $sender->sendMessage(Util::PREFIX . "Vous venez de donner un kit §e" . $args["kit"] . " §fau joueur §e" . $target->getName());
        $target->sendMessage(Util::PREFIX . "Vous venez de recevoir le kit §e" . $args["kit"] . " §fde la part de §e" . $sender->getName());
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetArgument("joueur"));
        $this->registerArgument(1, new OptionArgument("kit", array_keys(Kit::getKits())));
    }
}