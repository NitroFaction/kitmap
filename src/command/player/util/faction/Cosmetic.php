<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\faction;

use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use NCore\handler\Cache;
use NCore\handler\OtherAPI;
use NCore\handler\SkinAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Cosmetic extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "cosmetic",
            "Ouvre le menu des cosmetiques"
        );

        $this->setAliases(["cosmetiques"]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            $form = new SimpleForm(function (Player $player, mixed $data) use ($session) {
                if (!is_string($data)) {
                    return;
                }

                $cosmetics = $session->data["player"]["cosmetics"];

                if ($data === "remove") {
                    $player->sendMessage(Util::PREFIX . "Votre skin a été réstauré, vous n'avez plus votre cosmetique d'activé");

                    $session->data["player"]["cosmetic"] = [null, null];
                    SkinAPI::setDefaultSkin($player);
                } else if (in_array($data, $cosmetics)) {
                    if (count(SkinAPI::$skins[$data]["texture"]) === 0) {
                        $this->setCosmetic($player, $data);
                    } else {
                        $this->openCosmeticChoice($player, $data);
                    }
                } else {
                    $this->buyCosmetic($player, $data);
                }
            });
            $form->setTitle("Cosmétiques");

            foreach (Cache::$config["cosmetics"] as $key => $value) {
                $name = match (true) {
                    !in_array($key, $session->data["player"]["cosmetics"]) => "\n§cNon Débloqué",
                    $session->data["player"]["cosmetic"][0] === $key => "\n§eCosmétique Actuel",
                    default => ""
                };

                $form->addButton($value["name"] . $name, -1, "", $key);
            }

            $form->addButton("Supprimer son cosmetique", -1, "", "remove");
            $sender->sendForm($form);
        }
    }

    private function setCosmetic(Player $player, string $cosmetic, string $texture = "default"): void
    {
        $session = Session::get($player);
        $data = Cache::$config["cosmetics"][$cosmetic];

        $message = Util::PREFIX . "Vous venez de mettre le cosmetique §e" . $data["name"];

        if ($texture !== "default") {
            $message .= " §fde couleur §" . Cache::$config["colors"][$texture] . ucfirst($texture);
        }

        $session->data["player"]["cosmetic"] = [$cosmetic, $texture];
        SkinAPI::setCosmetic($player, $cosmetic, $texture);

        $player->sendMessage($message);
    }

    private function openCosmeticChoice(Player $player, string $cosmetic): void
    {
        $form = new SimpleForm(function (Player $player, mixed $data) use ($cosmetic) {
            if (!is_string($data)) {
                return;
            }

            $this->setCosmetic($player, $cosmetic, $data);
        });
        $form->setTitle("Cosmétiques");
        $form->setContent(Util::PREFIX . "Choisissez la couleur que vous voulez pour votre cosmétique");
        $form->addButton("Couleur par défaut", -1, "", "default");

        foreach (SkinAPI::$skins[$cosmetic]["texture"] as $key => $value) {
            if ($key !== "default") {
                $form->addButton("Couleur " . $key, -1, "", $key);
            }
        }

        $player->sendForm($form);
    }

    private function buyCosmetic(Player $player, string $cosmetic): void
    {
        $_data = Cache::$config["cosmetics"][$cosmetic];
        $session = Session::get($player);

        $form = new CustomForm(function (Player $player, mixed $data) use ($session, $cosmetic, $_data) {
            if (!is_array($data) || !isset($data[1]) || !isset($data[2]) || !is_bool($data[2]) || !$data[2]) {
                return;
            }

            $devise = match ($data[1]) {
                1 => "gemmes",
                default => "pièces"
            };

            $money = match ($data[1]) {
                1 => "gem",
                default => "money"
            };

            if ($_data[$money] > $session->data["player"][$money]) {
                $player->sendMessage(Util::PREFIX . "Vous ne possedez pas assez de " . $devise . " pour acheter le cosmétique §e" . $_data["name"]);
                return;
            } else if ($money === "gem") {
                $this->saveCosmetic($player->getXuid(), $cosmetic);
            }

            $session->data["player"]["cosmetics"][] = $cosmetic;

            $session->addValue($money, $_data[$money], true);
            $player->sendMessage(Util::PREFIX . "Vous venez d'acheter le cosmétique §e" . $_data["name"] . " §favec §e" . $_data[$money] . " §f" . $devise);
        });
        $form->setTitle("Cosmétiques");

        if ($_data["money"] > 0) {
            $form->addLabel(Util::PREFIX . $_data["description"] . "\n\n§fPrix: §e" . OtherAPI::format($_data["money"]) . " §fpièces ou §a" . $_data["gem"] . " §fgemmes\n\nVous possedez §e" . $session->data["player"]["gem"] . " §fgemme(s)\nVous possedez §e" . $session->data["player"]["money"] . " §fpièces(s)\n");
            $form->addDropdown("Méthode de payement", ["pièces", "gemmes"]);
            $form->addToggle("Acheter du cosmetique " . $_data["name"] . "?", true);
        } else {
            $form->addLabel(Util::PREFIX . $_data["description"] . "\n\nCelui-ci n'est pas achetable");
        }

        $player->sendForm($form);

    }

    private function saveCosmetic(string $value, string $key): void
    {
        $file = Util::getFile("ownings");
        $data = $file->get($value) ?? [];

        $data["cosmetics"][] = $key;

        $file->set($value, $data);
        $file->save();
    }

    protected function prepare(): void
    {
    }
}