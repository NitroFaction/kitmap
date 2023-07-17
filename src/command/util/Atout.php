<?php /** @noinspection PhpUnused */

namespace Kitmap\command\util;

use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use Kitmap\handler\Cache;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\data\bedrock\EffectIdMap;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Atout extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "atout",
            "Ouvre le menu des atouts"
        );

        $this->setAliases(["atouts"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            $form = new SimpleForm(function (Player $player, mixed $name) use ($session) {
                if (!is_string($name)) {
                    return;
                }

                $data = $session->data["atouts"][$name] ?? [false, false];
                $atout = Cache::$config["atouts"][$name];

                switch ($data[1]) {
                    case false:
                        $this->openBuyForm($player, $name);
                        return;
                    case true:
                        if ($data[0]) {
                            $session->data["atouts"][$name][0] = false;
                            $player->getEffects()->remove(EffectIdMap::getInstance()->fromId($atout["id"]));

                            $player->sendMessage(Util::PREFIX . "Vous venez de désactiver l'atout §e" . $name);
                        } else {
                            $session->data["atouts"][$name][0] = true;
                            Util::givePlayerPreferences($player);

                            $player->sendMessage(Util::PREFIX . "Vous venez d'activer l'atout §e" . $name);
                        }
                        return;
                }
            });

            $form->setTitle("Atouts");
            $form->setContent(Util::PREFIX . "Clique sur le bouton de ton choix");

            foreach (Cache::$config["atouts"] as $name => $atout) {
                $data = $session->data["atouts"][$name] ?? [false, false];

                $button = match (true) {
                    ($data[1] === false) => $name . "\n§cNon Acheté",
                    ($data[0] === false) => $name . "\n§cDésactivé",
                    default => $name . "\n§aActivé"
                };

                $form->addButton($button, 0, "textures/ui/" . $atout["texture"], $name);
            }

            $sender->sendForm($form);
        }
    }

    private function openBuyForm(Player $player, string $name): void
    {
        $atout = Cache::$config["atouts"][$name];
        $session = Session::get($player);

        $form = new CustomForm(function (Player $player, mixed $data) use ($name, $atout, $session) {
            if (!is_array($data) || !isset($data[1]) || !isset($data[2]) || !is_bool($data[2]) || !$data[2]) {
                return;
            }

            switch ($data[1]) {
                case 0:
                    if ($atout["price"] > $session->data["money"]) {
                        $player->sendMessage(Util::PREFIX . "Vous ne possedez pas assez de pièces pour acheter l'atout §e" . $name);
                        return;
                    }

                    $session->addValue("money", $atout["price"], true);
                    $session->data["atouts"][$name] = [true, true];

                    $player->sendMessage(Util::PREFIX . "Vous venez d'acheter l'atout §e" . $name . " §favec §e" . $atout["price"] . " §fpièces");
                    return;
                case 1:
                    if ($atout["gem"] > $session->data["gem"]) {
                        $player->sendMessage(Util::PREFIX . "Vous ne possedez pas assez de gemmes pour acheter l'atout §e" . $name);
                        return;
                    }

                    $session->addValue("gem", $atout["gem"], true);
                    $session->data["atouts"][$name] = [true, true];

                    $player->sendMessage(Util::PREFIX . "Vous venez d'acheter l'atout §e" . $name . " §favec §e" . $atout["gem"] . " §fgemmes");
                    return;
            }

            Util::givePlayerPreferences($player);
        });
        $form->setTitle("Atouts");
        $form->addLabel(Util::PREFIX . "L'atout vous donnera l'effet de " . $name . " constament lorsque l'atout sera activé\n\nPrix: §e" . $atout["price"] . " §fpièces ou §a" . $atout["gem"] . " §fgemmes\n\nVous possedez §e" . $session->data["gem"] . " §fgemme(s)\nVous possedez §e" . $session->data["money"] . " §fpièces(s)\n");
        $form->addDropdown("Méthode de payement", ["pièces", "gemmes"]);
        $form->addToggle("Acheter l'atout de " . $name . "?", true);
        $player->sendForm($form);
    }

    protected function prepare(): void
    {
    }
}