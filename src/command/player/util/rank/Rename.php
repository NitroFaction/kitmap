<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\rank;

use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\CustomForm;
use NCore\Base;
use NCore\handler\RankAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\item\ItemIds;
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
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if (!RankAPI::hasRank($sender, "champion")) {
                $sender->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de faire cela");
                return;
            }

            if ($session->inCooldown("rename")) {
                $seconds = $session->getCooldownData("rename")[0] - time();

                $_seconds = $seconds % 60;
                $minutes = floor(($seconds % 3600) / 60);

                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez actuellement pas faire de /rename, merci d'attendre §e" . $minutes . " §fminutes et §e" . $_seconds . " §fsecondes !");
            }

            $form = new CustomForm(function (Player $player, mixed $data) use ($session) {
                if (!is_array($data) || !isset($data[0]) || 1 > strlen($data[0])) {
                    return;
                }

                if ($player->getInventory()->getItemInHand()->getId() >= 1) {
                    $session->setCooldown("rename", round((60 * 3)));
                    $item = $player->getInventory()->getItemInHand()->setCustomName("§r§f" . $data[0]);

                    if ($item->getId() === ItemIds::PAPER || !is_null($item->getNamedTag()->getTag("partneritem")) || !is_null($item->getNamedTag()->getTag("type")) || !is_null($item->getNamedTag()->getTag("data"))) {
                        $player->sendMessage(Util::PREFIX . "L'item dans votre main ne peut pas être renommé !");
                        return;
                    }

                    $player->getInventory()->setItemInHand($item);
                    $player->sendMessage(Util::PREFIX . "Vous venez de renommer l'item dans votre main en " . $data[0]);

                    Base::getInstance()->getLogger()->info("Le joueur " . $player->getName() . " vient de renommer l'item dans sa main en " . $data[0]);
                } else {
                    $player->sendMessage(Util::PREFIX . "L'item dans votre main ne peut pas être renommer");
                }
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