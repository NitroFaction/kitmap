<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\rank;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use NCore\handler\RankAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Repair extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "repair",
            "Repair les items dans l'inventaire ou la main d'un joueur"
        );
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if (!RankAPI::hasRank($sender, "champion")) {
                $sender->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de faire cela");
                return;
            } else if ($session->inCooldown("repair")) {
                $seconds = $session->getCooldownData("repair")[0] - time();

                $_seconds = $seconds % 60;
                $minutes = floor(($seconds % 3600) / 60);

                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez actuellement pas réparer vos items, merci d'attendre §e" . $minutes . " §fminutes et §e" . $_seconds . " §fsecondes !");
                return;
            } else if ($session->inCooldown("combat")) {
                $sender->sendMessage(Util::PREFIX . "Cette commande est interdite en combat");
                return;
            }

            if (isset($args["opt"]) && $args["opt"] === "all") {
                if (!RankAPI::hasRank($sender, "elite")) {
                    $sender->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de faire cela");
                    return;
                }

                foreach ($sender->getInventory()->getContents() as $index => $item) $this->repairItem($item, $index, $sender->getInventory());
                foreach ($sender->getArmorInventory()->getContents() as $index => $item) $this->repairItem($item, $index, $sender->getArmorInventory());

                $session->setCooldown("repair", round((60 * 10)));
                $sender->sendMessage(Util::PREFIX . "Vous venez de réparer tous les items dans votre inventaire");

                return;
            }

            $index = $sender->getInventory()->getHeldItemIndex();
            $item = $sender->getInventory()->getItem($index);

            $repair = $this->repairItem($item, $index, $sender->getInventory());

            if (!$repair) {
                $sender->sendMessage(Util::PREFIX . "L'item dans votre main ne peut pas être réparé");
            } else {
                $sender->sendMessage(Util::PREFIX . "L'item dans votre main a été réparé");
                $session->setCooldown("repair", round((60)));
            }
        }
    }

    private function repairItem(Item $item, int $index, $inventory): bool
    {
        if ($item instanceof Durable) {
            $inventory->setItem($index, $item->setDamage(0));
            return true;
        }
        return false;
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("opt", ["all"], true));
    }
}