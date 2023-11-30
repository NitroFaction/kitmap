<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player\rank;

use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\CustomForm;
use Kitmap\handler\Rank;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Fly extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "fly",
            "Permet de voler sur les iles de faction"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if (!Rank::hasRank($sender, "champion") && !$session->data["fly"]) {
                $this->openBuyForm($sender);
                return;
            }

            if (!$sender->getAllowFlight()) {
                if ($sender->getGamemode() === GameMode::SURVIVAL()) {
                    if (!str_starts_with($sender->getWorld()->getFolderName(), "island-")) {
                        $sender->sendMessage(Util::PREFIX . "Vous ne pouvez voler que dans les iles de faction");
                        return;
                    }

                    $sender->setAllowFlight(true);
                    $sender->sendMessage(Util::PREFIX . "Vous pouvez désormais voler");
                } else {
                    $sender->sendMessage(Util::PREFIX . "Vous ne pouvez activer le fly seulement en survie");
                }
            } else {
                $sender->setAllowFlight(false);
                $sender->setFlying(false);
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez désormais plus voler");
            }
        }
    }

    private function openBuyForm(Player $player): void
    {
        $session = Session::get($player);

        $form = new CustomForm(function (Player $player, mixed $data) use ($session) {
            if (!is_array($data) || !isset($data[1]) || !is_bool($data[1]) || !$data[1]) {
                return;
            }

            $money = $session->data["money"];

            if ($money >= 1000000) {
                $session->addValue("money", 1000000, true);
                $session->data["fly"] = true;
                $player->sendMessage(Util::PREFIX . "Vous venez d'acheter la commande §q/fly §favec §q1M §fpièces");
            } else {
                $player->sendMessage(Util::PREFIX . "Vous ne possedez pas assez de pièces pour acheter la commande §q/fly");
            }

            Util::givePlayerPreferences($player);
        });

        $form->setTitle("Fly");
        $form->addLabel(Util::PREFIX . "Cette commande vous donnera la possibilité de voler dans votre île de faction !\n\nPrix: §q1M §fpièces\n\nVous possedez §q" . Util::formatNumberWithSuffix($session->data["money"]) . " §fpièces(s)\n");
        $form->addToggle("Acheter la commande /fly ?", true);

        $player->sendForm($form);
    }

    protected function prepare(): void
    {
    }
}