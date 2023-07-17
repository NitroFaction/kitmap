<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\rank;

use CortexPE\Commando\args\TargetArgument;
use CortexPE\Commando\BaseCommand;
use NCore\Base;
use NCore\handler\RankAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use Util\util\IdsUtils;

class Stuff extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "stuff",
            "Accède à l'inventaire d'un autre joueur connecté"
        );
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $target = Base::getInstance()->getServer()->getPlayerByPrefix($args["joueur"]);

        if (!$target instanceof Player) {
            $sender->sendMessage(Util::PREFIX . "Le joueur indiqué n'est pas connecté sur le serveur");
            return;
        }

        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if (!RankAPI::hasRank($sender, "elite")) {
                $sender->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de faire cela");
                return;
            }

            if ($session->inCooldown("stuff")) {
                $seconds = $session->getCooldownData("stuff")[0] - time();

                $_seconds = $seconds % 60;
                $minutes = floor(($seconds % 3600) / 60);

                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez actuellement pas faire de /stuff, merci d'attendre §e" . $minutes . " §fminutes et §e" . $_seconds . " §fsecondes !");
            }

            $session->setCooldown("stuff", round((60 * 30)));
            $inventory = $this->getInventory($target);

            $sender->sendMessage(Util::PREFIX . "Inventaire de §e" . $target->getDisplayName());
            $sender->sendMessage("§e" . $inventory["iris"] . " §fpièces en iris et §e" . $inventory["emerald"] . " §fpièces en émeraude");
            $sender->sendMessage("§e" . $inventory["potion"] . " §fpotions et §e" . $inventory["cookie"] . " §fcookies");
            $target->sendMessage(Util::PREFIX . "§e" . $sender->getDisplayName() . " §fvérifie votre stuff...");
        }
    }

    private function getInventory(Player $target): array
    {
        $result = [
            "iris" => 0,
            "emerald" => 0,
            "potion" => 0,
            "cookie" => 0
        ];

        $content = array_merge($target->getArmorInventory()->getContents(), $target->getInventory()->getContents());

        foreach ($content as $item) {
            if ($item->getId() === IdsUtils::IRIS_BOOTS || $item->getId() === IdsUtils::IRIS_LEGGINGS || $item->getId() === IdsUtils::IRIS_CHESTPLATE || $item->getId() === IdsUtils::IRIS_HELMET || $item->getId() === IdsUtils::IRIS_SWORD) {
                $result["iris"]++;
            } else if ($item->getId() === IdsUtils::EMERALD_BOOTS || $item->getId() === IdsUtils::EMERALD_LEGGINGS || $item->getId() === IdsUtils::EMERALD_CHESTPLATE || $item->getId() === IdsUtils::EMERALD_HELMET || $item->getId() === IdsUtils::EMERALD_SWORD) {
                $result["emerald"]++;
            } else if ($item->getId() === ItemIds::SPLASH_POTION) {
                $result["potion"]++;
            } else if ($item->getId() === IdsUtils::REGENERATION_COOKIE || $item->getId() === IdsUtils::SPEED_COOKIE || $item->getId() === IdsUtils::STRENGTH_COOKIE || $item->getId() === IdsUtils::COMBINED_COOKIE) {
                $result["cookie"]++;
            }
        }
        return $result;
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetArgument("joueur"));
    }
}