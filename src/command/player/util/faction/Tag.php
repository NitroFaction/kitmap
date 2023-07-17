<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\faction;

use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use NCore\handler\Cache;
use NCore\handler\OtherAPI;
use NCore\handler\RankAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Tag extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "tag",
            "Ouvre le menu des tags"
        );
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            $form = new SimpleForm(function (Player $player, mixed $data) use ($session) {
                if (!is_string($data)) {
                    return;
                }

                $tags = $session->data["player"]["tags"];

                if ($data === "remove") {
                    $player->sendMessage(Util::PREFIX . "Votre venez d'enlever votre tag");
                    $session->data["player"]["tag"] = null;

                    RankAPI::updateNameTag($player);
                } else if (in_array($data, $tags)) {
                    $this->setTag($player, $data);
                } else {
                    $this->buyTag($player, $data);
                }
            });
            $form->setTitle("Tags");

            foreach (Cache::$config["tags"] as $key => $value) {
                $name = match (true) {
                    !in_array($key, $session->data["player"]["tags"]) => "\n§cNon Débloqué",
                    $session->data["player"]["tag"] === $key => "\n§eTag Actuel",
                    default => ""
                };

                $form->addButton($value . $name, -1, "", $key);
            }

            $form->addButton("Supprimer son tag", -1, "", "remove");
            $sender->sendForm($form);
        }
    }

    private function setTag(Player $player, string $tag): void
    {
        $session = Session::get($player);
        $data = Cache::$config["tags"][$tag];

        $session->data["player"]["tag"] = $tag;
        $player->sendMessage(Util::PREFIX . "Vous venez d'activer le tag " . $data);
    }

    private function buyTag(Player $player, string $tag): void
    {
        $_data = Cache::$config["tags"][$tag];
        $session = Session::get($player);

        $price = [
            "gem" => 50,
            "money" => 100000
        ];

        $form = new CustomForm(function (Player $player, mixed $data) use ($session, $tag, $price, $_data) {
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

            if ($price[$money] > $session->data["player"][$money]) {
                $player->sendMessage(Util::PREFIX . "Vous ne possedez pas assez de " . $devise . " pour acheter le tag " . $_data);
                return;
            } else if ($money === "gem") {
                $this->saveTag($player->getXuid(), $tag);
            }

            $session->data["player"]["tags"][] = $tag;

            $session->addValue($money, $price[$money], true);
            $player->sendMessage(Util::PREFIX . "Vous venez d'acheter le tag §e" . $_data . " §favec §e" . $price[$money] . " §f" . $devise);
        });
        $form->setTitle("Tags");
        $form->addLabel(Util::PREFIX . "Apercu du tag: " . $_data . "\n\n§fPrix: §e" . OtherAPI::format($price["money"]) . " §fpièces ou §a" . $price["gem"] . " §fgemmes\n\nVous possedez §e" . $session->data["player"]["gem"] . " §fgemme(s)\nVous possedez §e" . $session->data["player"]["money"] . " §fpièces(s)\n");
        $form->addDropdown("Méthode de payement", ["pièces", "gemmes"]);
        $form->addToggle("Acheter le tag " . $_data . "?", true);
        $player->sendForm($form);
    }

    private function saveTag(string $value, string $key): void
    {
        $file = Util::getFile("ownings");
        $data = $file->get($value) ?? [];

        $data["tags"][] = $key;

        $file->set($value, $data);
        $file->save();
    }

    protected function prepare(): void
    {
    }
}