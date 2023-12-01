<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player;

use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\SimpleForm;
use Kitmap\handler\Cache;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use Kitmap\handler\Jobs as Api;

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
            $form->setTitle("Jobs");
            $form->addButton("§8Mineur§q: §8" . Api::getProgressBar($sender, "Mineur", "UI") . "\n" . Api::getProgressBar($sender, "Mineur"), -1, "", "mineur");
            $form->addButton("§8Farmeur§q: §8" . Api::getProgressBar($sender, "Farmeur", "UI") . "\n" . Api::getProgressBar($sender, "Farmeur"), -1, "", "farmeur");
            $form->addButton("§8Hunter§q: §8" . Api::getProgressBar($sender, "Hunter", "UI") . "\n" . Api::getProgressBar($sender, "Hunter"), -1, "", "hunter");
            $sender->sendForm($form);
        }
    }

    private function jobInformation(Player $player, string $job): void
    {
        $form = new SimpleForm(null);
        $form->setTitle("Jobs");

        $label = Util::PREFIX . "§qMétier de " . $job . "\n\n";

        switch ($job) {
            case "mineur":
                $label .= "§fPierre: §q1xp\n§fPierre taillé: §q1xp\n§fLuckyBlock: §q5xp\n§fEmeraude: §q15xp";
                break;
            case "farmeur":
                $label .= "§fBlé: §q1-3xp\n§fCarrote: §q1-3xp\n§fBetterave: §q1-3xp\n§fPatate: §q1-3xp\n§fMelon: §q1-3xp\n§fBambou: §q1xp\n\n§fGraines en Iris: §q5xp";
                break;
            case "hunter":
                $label .= "§fKill: §q50xp\n§fPlus votre killstreak (exemple: 50 + 10)";
                break;
        }

        $label .= "\n\n" . Util::PREFIX . "§qRécomponses:\n\n";

        for ($i = 2; $i <= 20; $i++) {
            $data = Cache::$config["jobs"]["rewards"][strval($i)];
            $data = explode(":", $data);

            $name = match (intval($data[0])) {
                0 => $data[4],
                1 => $data[6],
                default => "un partneritem aléatoire",
            };

            $label .= "§fNiveau " . $i . ": §q" . ucfirst(strtolower($name)) . "\n";
        }

        $form->setContent($label);
        $form->addButton("Quitter");
        $player->sendForm($form);
    }

    protected function prepare(): void
    {
    }
}