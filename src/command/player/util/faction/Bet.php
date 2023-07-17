<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\faction;

use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use NCore\handler\Cache;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Bet extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "bet",
            "Permet de miser ou de regarder les score en direct des matchs"
        );

        $this->setAliases(["paris"]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $sender->sendMessage(Util::PREFIX . "Les paris sont actuellement désactivés");
            return;

            $list = self::getGamesData();

            $form = new SimpleForm(function (Player $player, mixed $data) {
                if (!is_string($data)) {
                    return;
                }

                $this->betForm($player, $data);
            });
            $form->setTitle("Paris Sportif (§eBeta§r)");
            $form->setContent(Util::PREFIX . "Cliquez sur le match de votre choix pour miser");

            foreach ($list as $key => $value) {
                if ($value["status"] === "done") {
                    continue;
                }

                $squads = $value["home"] . " - " . $value["outside"];

                $description = match ($value["status"]) {
                    "upcoming" => $squads . "\n" . ucfirst($value["date"]) . " " . $value["hour"],
                    "ongoing" => $squads . "\n" . str_replace(":", " - ", $value["score"]) . " (" . $value["timer"] . ")",
                };

                $form->addButton($description, -1, "", $key);
            }

            $sender->sendForm($form);
        }
    }

    public static function getGamesData(): array
    {
        $file = new Config(Cache::$config["games_path"], Config::JSON);
        return $file->getAll() ?? [];
    }

    private function betForm(Player $player, string $id): void
    {
        $list = self::getGamesData();
        $game = $list[$id] ?? false;

        $bet = Cache::$dynamic["bet"][$id][$player->getName()] ?? false;

        if (is_bool($game)) {
            return;
        } else if ($bet !== false) {
            $format = match ($bet[2]) {
                0 => "la victoire de §e" . $game["home"],
                1 => "un §ematch nul",
                2 => "la victoire de §e" . $game["outside"]
            };

            $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas parier sur ce match car vous avez déjà parié à propos de celui-ci ! Vous avez misé §e" . $bet[0] . " §fpièces sur " . $format . "§f, la cote était élevé à §e" . $bet[1] . " §f! Bonne chance à vous !");
            return;
        }

        $form = new CustomForm(function (Player $player, mixed $data) use ($id) {
            if (!is_array($data)) {
                return;
            }

            $session = Session::get($player);
            $list = self::getGamesData();

            $game = $list[$id];
            $bet = $data[1];

            if (1 > $amount = intval($data[2])) {
                $player->sendMessage(Util::PREFIX . "Le montant indiqué est invalide");
                return;
            } else if (floor($amount) > $session->data["player"]["money"]) {
                $player->sendMessage(Util::PREFIX . "Votre monnaie est infèrieur à §e" . floor($amount));
                return;
            }

            $format = match ($bet) {
                0 => "la victoire de §e" . $game["home"],
                1 => "un §ematch nul",
                2 => "la victoire de §e" . $game["outside"]
            };

            $odd = match ($bet) {
                0 => $game["home_odd"],
                1 => $game["neutral_odd"],
                2 => $game["outside_odd"]
            };

            $session->addValue("money", $amount, true);
            $player->sendMessage(Util::PREFIX . "Vous venez de miser sur §e" . $game["home"] . " - " . $game["outside"] . " §f! §fVous avez mis §e" . $amount . " §fpièces sur " . $format . "§f, la cote s'éleve à §e" . $odd . " §f! Si vous gagnez, votre récompensera sera de §e" . $amount * $odd . " §f! Bonne chance à toi !");

            Cache::$dynamic["bet"][$id][$player->getName()] = [$amount, $odd, $bet];
        });
        $form->setTitle("Paris Sportif (§eBeta§r)");
        $form->addLabel(Util::PREFIX . "§e" . $game["home"] . " §f- §e" . $game["outside"] . "\n§e(§fALERTE§e) §fFAITES ATTENTION §e/§f!§e\\\n\n§fLorsque vous avez pariez vous ne pouvez ni retirer votre paris ni le changer, §freflechissez donc bien avant de pariez !\n\n" . Util::PREFIX . "Les cotes sont le multiplicateur de votre argent lorsque vous gagnez");
        $form->addDropdown("Paris", [
            "Gagnant: " . $game["home"] . " | Cote: " . $game["home_odd"],
            "Match Nul | Cote: " . $game["neutral_odd"],
            "Gagnant: " . $game["outside"] . " | Cote: " . $game["outside_odd"]
        ]);
        $form->addInput("Montant");
        $player->sendForm($form);
    }

    protected function prepare(): void
    {
    }
}