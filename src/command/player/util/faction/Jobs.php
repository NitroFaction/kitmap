<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\faction;

use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\SimpleForm;
use NCore\handler\Cache;
use NCore\handler\JobsAPI;
use NCore\Util;
use pocketmine\command\CommandSender;
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
            $form->addButton("§8Mineur§e: §8" . JobsAPI::getProgressBar($sender, "Mineur", "UI") . "\n" . JobsAPI::getProgressBar($sender, "Mineur"), -1, "", "mineur");
            $form->addButton("§8Farmeur§e: §8" . JobsAPI::getProgressBar($sender, "Farmeur", "UI") . "\n" . JobsAPI::getProgressBar($sender, "Farmeur"), -1, "", "farmeur");
            $form->addButton("§8Hunter§e: §8" . JobsAPI::getProgressBar($sender, "Hunter", "UI") . "\n" . JobsAPI::getProgressBar($sender, "Hunter"), -1, "", "hunter");
            $sender->sendForm($form);
        }
    }

    private function jobInformation(Player $player, string $job): void
    {
        $form = new SimpleForm(null);
        $form->setTitle("Jobs");

        $label = Util::PREFIX . "§eMétier de " . $job . "\n\n";

        switch ($job) {
            case "mineur":
                $label .= "§fPierre: §e1xp\n§fPierre taillé: §e1xp\n§fDiamant: §e5xp\n§fEmeraude: §e15xp";
                break;
            case "farmeur":
                $label .= "§fBlé: §e1-3xp\n§fCarrote: §e1-3xp\n§fBetterave: §e1-3xp\n§fPatate: §e1-3xp\n§fMelon: §e1-3xp\n§fBambou: §e1xp";
                break;
            case "hunter":
                $label .= "§fKill: §e50xp\n§fPlus votre killstreak (exemple: 50 + 10)";
                break;
        }

        $label .= "\n\n" . Util::PREFIX . "§eRécomponses:\n\n";

        for ($i = 2; $i <= 20; $i++) {
            $data = Cache::$config["jobs"]["rewards"][strval($i)];
            $data = explode(":", $data);

            $name = match (intval($data[0])) {
                0 => $data[4],
                1 => $data[6],
                default => "un partneritem aléatoire",
            };

            $label .= "§fNiveau " . $i . ": §e" . ucfirst(strtolower($name)) . "\n";
        }

        $form->setContent($label);
        $form->addButton("Quitter");
        $player->sendForm($form);
    }

    protected function prepare(): void
    {
    }
}