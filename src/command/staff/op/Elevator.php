<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\SimpleForm;
use Kitmap\handler\Cache;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\world\Position;

class Elevator extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "elevator",
            "Permet de se téléporter à un étage de la mine directement"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $teleport = $args["opt"] ?? null;

            if (is_null($teleport)) {
                self::openForm($sender);
                return;
            }

            $this->teleportTo($sender, $teleport);
        }
    }

    public static function openForm(Player $player): void
    {
        $form = new SimpleForm(function (Player $player, mixed $data) {
            if (!is_string($data)) {
                return;
            }

            Elevator::teleportTo($player, $data);
        });
        $form->setTitle("Ascenseur");
        $form->setContent(Util::PREFIX . "Cliquez sur l'étage de votre choix");
        $form->addButton("Etage des cookies", -1, "", "cookie");
        $form->addButton("Etage des luckyblock", -1, "", "luckyblock");
        $form->addButton("Etage des pieces", -1, "", "money");
        $form->addButton("Etage de la netherite", -1, "", "netherite");
        $form->addButton("Etage de l'emeraude & netherite", -1, "", "minerais");
        $form->addButton("Etage de l'emeraude", -1, "", "emeraude");
        $player->sendForm($form);
    }

    private static function teleportTo(Player $player, string $teleport): void
    {
        if (!in_array($teleport, array_keys(Cache::$config["elevator"]))) {
            return;
        }

        list($x, $y, $z) = explode(":", Cache::$config["elevator"][$teleport]);
        $player->teleport(new Position(floatval($x), floatval($y), floatval($z), $player->getWorld()));
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("opt", ["cookie", "luckyblock", "money", "netherite", "minerais", "emeraude"], true));
    }
}