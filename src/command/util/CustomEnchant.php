<?php

namespace Kitmap\command\util;

use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\SimpleForm;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class CustomEnchant extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "customenchant",
            "Obtenir des informations sur les enchantements customs"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $this->openListEnchantForm($sender);
        }
    }

    private function openListEnchantForm(Player $player): void
    {
        $form = new SimpleForm(function (Player $player, mixed $data) {
            if (!is_string($data)) {
                return;
            }

            $this->openInformationsEnchantForm($player, $data);
        });

        $form->setTitle("Custom Enchant");
        $form->setContent(Util::PREFIX . "Bienvenue dans le menu des custom enchant ! Veuillez cliquer sur l'enchantement sur lequel vous souhaitez obtenir ses informations !");
        $form->addButton("§8Pilleur", label: "Pilleur");
        $form->addButton("§8Foudroiement", label: "Foudroiement");
        $form->addButton("§8Arès", label: "Arès");

        $player->sendForm($form);
    }

    private function openInformationsEnchantForm(Player $player, string $enchant): void
    {
        $form = new SimpleForm(fn(Player $player, mixed $data) => $this->openListEnchantForm($player));

        $form->setTitle($enchant);
        $form->setContent($this->getInformationsByEnchant($enchant));

        $player->sendForm($form);
    }

    private function getInformationsByEnchant(string $enchant): string
    {
        return match ($enchant) {
            "Pilleur" => "§l§9» §r§9Description §l§9«\n§r§fCet enchantement vous permet, dès que vous tuez un joueur, de lui voler un certain pourcentage des pièces.\n\n§l§9» §r§9Niveaux §l§9«\n§r§f1 §8-> §9%2%%\n§f2 §8-> §9%4%%\n§f3 §8-> §9%6%%",
            "Foudroiement" => "§l§9» §r§9Description §l§9«\n§r§fCet enchantement peut vous permettre, dès que vous attaquez un joueur et si vous avez de la chance, de lui faire apparaître un éclair dessus qui lui retirera subitement 1.5 HP.\n\n§l§9» §r§9Niveaux §l§9«\n§r§f1 §8-> §91/200\n§f2 §8-> §91/150\n§f3 §8-> §91/100",
            "Arès" => "§l§9» §r§9Description §l§9«\n§r§fCet enchantement vous permet, lorsque vous tuez un joueur, d'afficher un compteur de kill qui augmentera au fil des kills que vous ferez avec la même épée.\n\n§l§9» §r§9Niveaux §l§9«\n§r§fCet enchantement ne possède qu'un seul niveau",
            default => "§cAucune description n'est disponible pour cet enchantement."
        };
    }

    protected function prepare(): void
    {
    }
}