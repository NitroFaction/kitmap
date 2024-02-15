<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player;

use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\SimpleForm;
use Kitmap\handler\Cache;
use Kitmap\handler\Jobs as Api;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Jobs extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "jobs",
            "Ouvre le menu des jobs"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $form = new SimpleForm(function (Player $player, mixed $data) {
                if (!is_string($data) || !in_array($data, ["mineur", "farmeur", "hunter"])) {
                    return;
                }

                $this->jobInformation($player, $data);
            });
            $form->setTitle("Métiers");
            $form->addButton("§8Mineur§9: §8" . Api::getProgressBar($sender, "Mineur", "UI") . "\n" . Api::getProgressBar($sender, "Mineur"), -1, "", "mineur");
            $form->addButton("§8Farmeur§9: §8" . Api::getProgressBar($sender, "Farmeur", "UI") . "\n" . Api::getProgressBar($sender, "Farmeur"), -1, "", "farmeur");
            $form->addButton("§8Hunter§9: §8" . Api::getProgressBar($sender, "Hunter", "UI") . "\n" . Api::getProgressBar($sender, "Hunter"), -1, "", "hunter");
            $sender->sendForm($form);
        }
    }

    private function jobInformation(Player $player, string $job): void
    {
        $form = new SimpleForm(null);
        $form->setTitle("Métiers");

        $label = Util::PREFIX . "§9Métier de " . $job . "\n\n";

        switch ($job) {
            case "mineur":
                $label .= "§fPierre: §91xp\n§fPierre taillé: §91xp\n§fLuckyBlock: §95xp\n§fEmeraude: §915xp";
                break;
            case "farmeur":
                $label .= "§fBlé: §91-3xp\n§fCarrote: §91-3xp\n§fBetterave: §91-3xp\n§fPatate: §91-3xp\n§fMelon: §91-3xp\n§fBambou: §91xp\n\n§fGraines en Iris: §95xp";
                break;
            case "hunter":
                $label .= "§fKill: §950xp\n§fPlus votre killstreak (exemple: 50 + 10)";
                break;
        }

        $label .= "\n\n" . Util::PREFIX . "§9Récomponses:\n\n";

        for ($i = 2; $i <= 20; $i++) {
            $data = Cache::$config["jobs"]["rewards"][strval($i)];
            $data = explode(":", $data);

            $name = match (intval($data[0])) {
                0 => $data[3],
                1 => $data[5],
                default => "un partneritem aléatoire",
            };

            $label .= "§fNiveau " . $i . ": §9" . ucfirst(strtolower($name)) . "\n";
        }

        $form->setContent($label);
        $form->addButton("Quitter");
        $player->sendForm($form);
    }

    protected function prepare(): void
    {
    }
}