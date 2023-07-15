<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player\rank;

use CortexPE\Commando\args\TargetArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Rank;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Stuff extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "stuff",
            "Accède à l'inventaire d'un autre joueur connecté"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            /** @noinspection PhpDeprecationInspection */
            $player = Main::getInstance()->getServer()->getPlayerByPrefix($args["joueur"]);

            if (!Rank::hasRank($sender, "elite")) {
                $sender->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de faire cela");
                return;
            } else if ($session->inCooldown("stuff")) {
                $format = Util::formatDurationFromSeconds($session->getCooldownData("stuff")[0] - time());
                $sender->sendMessage(Util::PREFIX . "Vous ne pourrez ré-utiliser la commande §e/stuff §fque dans: §e" . $format);
                return;
            } else if (!$player instanceof Player) {
                $sender->sendMessage(Util::PREFIX . "Le joueur indiqué n'est pas connecté sur le serveur");
                return;
            }

            $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);

            $menu->setListener(InvMenu::readonly());
            $menu->setName("Inventaire de " . $player->getName());

            foreach ($player->getInventory()->getContents(true) as $slot => $item) {
                $menu->getInventory()->setItem($slot, $item);
            }

            $menu->getInventory()->setItem(46, $player->getArmorInventory()->getHelmet());
            $menu->getInventory()->setItem(48, $player->getArmorInventory()->getChestplate());
            $menu->getInventory()->setItem(50, $player->getArmorInventory()->getLeggings());
            $menu->getInventory()->setItem(52, $player->getArmorInventory()->getBoots());

            $menu->send($sender);
            $session->setCooldown("stuff", 60 * 5);
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetArgument("joueur"));
    }
}