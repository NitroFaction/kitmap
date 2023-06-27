<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\tool;

use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;

class ResetBourse extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "resetbourse",
            "Met à jour les prix de la bourse"
        );

        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        Cache::$data["bourse"] = [
            "Cactus:81:0:" . ($cactus = mt_rand(1, 3)) * 3 . ":" . $cactus,
            "Betterave:457:0:" . ($beetroot = mt_rand(3, 6)) * 3 . ":" . $beetroot,
            "Pomme de Terre:392:0:" . ($potato = mt_rand(4, 6)) * 3 . ":" . $potato,
            "Pastèque:360:0:3:1",
            "Carotte:391:0:" . ($carrot = mt_rand(4, 6)) * 3 . ":" . $carrot,
            "Bambou:-163:0:" . ($bamboo = mt_rand(3, 6)) * 3 . ":" . $bamboo,
            "Baies sucrées:477:0:" . ($sweetberry = mt_rand(2, 8)) * 3 . ":" . $sweetberry,
            "Blé:296:0:" . ($wheat = mt_rand(3, 6)) * 3 . ":" . $wheat,
            "Cannes à Sucres:338:0:" . ($sugarcan = mt_rand(2, 4)) * 3 . ":" . $sugarcan
        ];

        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "La bourse a été reset ! Les prix des agricultures ont donc changés");
    }

    protected function prepare(): void
    {
    }
}