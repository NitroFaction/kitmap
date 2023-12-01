<?php /* @noinspection PhpUnused */

namespace Kitmap\command\staff;

use CortexPE\Commando\BaseCommand;
use Element\util\args\OptionArgument;
use Kitmap\Main;
use Kitmap\task\repeat\FarmingWarsTask;
use Kitmap\Util;
use pocketmine\block\ItemFrame;
use pocketmine\block\VanillaBlocks;
use pocketmine\command\CommandSender;
use pocketmine\item\VanillaItems;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;

class FarmingWars extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "farmingwars",
            "Commence ou arrête un event farmingwars !"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        switch ($args["opt"]) {
            case "start":
                if (!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
                    $sender->sendMessage(Util::PREFIX . "Vous n'avez pas la permission d'utiliser cette commande.");
                    return;
                }

                if (FarmingWarsTask::$currentFarmingWars) {
                    $sender->sendMessage(Util::PREFIX . "Un event §qFarmingWars §fest déjà en cours... Vous pouvez l'arrêter avec la commande §q/farmingwars end");
                    return;
                }

                $crops = FarmingWarsTask::getAllCrops();
                $randomCrop = $crops[array_rand($crops)];
                FarmingWarsTask::start($randomCrop);

                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Un event §qFarmingWars §fvient de commencer ! Vous devez récupérer le plus de §q" . FarmingWarsTask::getTranslatedName($randomCrop) . " §fpossible dans un temps imparti de §q15 minutes §f!");
                break;
            case "end":
                if (!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
                    $sender->sendMessage(Util::PREFIX . "Vous n'avez pas la permission d'utiliser cette commande.");
                    return;
                }

                if (!FarmingWarsTask::$currentFarmingWars) {
                    $sender->sendMessage(Util::PREFIX . "Aucun event §qFarmingWars §fest en cours... Vous pouvez en démarrer un avec la commande §q/farmingwars start");
                    return;
                }

                FarmingWarsTask::reset();
                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "L'event §qFarmingWars §fa été arrêté");
                break;
            case "leaderboard":
                if (!FarmingWarsTask::$currentFarmingWars) {
                    $sender->sendMessage(Util::PREFIX . "Aucun event §qFarmingWars §fest en cours... Vous pouvez en démarrer un avec la commande §q/farmingwars start");
                    return;
                }

                $sender->sendMessage("§l§q» §r§aClassement du FarmingWars actuel §l§q«§r");

                $baseLeaderboard = FarmingWarsTask::getArsortedLeaderboard();
                $leaderboard = array_slice($baseLeaderboard, 0, 10, true);

                $i = 1;
                foreach ($leaderboard as $player => $score) {
                    $sender->sendMessage("§a#" . $i . ". §f" . $player . " §8(§7" . $score . "§8)");
                    $i++;
                }

                $senderPosition = array_search($sender->getName(), $baseLeaderboard);

                if ($senderPosition !== false && $senderPosition > 10) {
                    $sender->sendMessage(Util::PREFIX . "§fVotre position : §q" . intval($senderPosition));
                }
                break;
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("opt", ["start", "end", "leaderboard"]));
    }
}