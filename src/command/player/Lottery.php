<?php

namespace Kitmap\command\player;

use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use Kitmap\Session;
use Kitmap\task\repeat\LotteryTask;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Lottery extends BaseCommand
{

    public static array $bets = [];

    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "lottery",
            "Miser de l'argent à la lotterie"
        );

        $this->setAliases(["lotto"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $this->openMainForm($sender);
        }
    }

    private function openMainForm(Player $player): void
    {
        $form = new SimpleForm(function (Player $player, ?int $data = null) {
            if (!is_int($data)) {
                return;
            }

            switch ($data) {
                case 0:
                    $this->openBetForm($player);
                    break;
                case 1:
                    $this->openActualBetsForm($player);
                    break;
                case 2:
                    $this->openCancelBetForm($player);
                    break;
            }
        });

        $form->setTitle("Lotterie");
        $form->setContent(Util::PREFIX . "Bienvenue dans l'interface de la lotterie ! Que souhaitez-vous faire ?");
        $form->addButton("Miser");
        $form->addButton("Consulter les mises");

        if (array_key_exists(Util::getUnderscoredName($player), self::$bets)) {
            $form->addButton("Annuler sa mise");
        }

        $player->sendForm($form);
    }

    private function openBetForm(Player $player): void
    {
        $session = Session::get($player);
        $name = Util::getUnderscoredName($player);

        $actualBet = self::$bets[$name] ?? 0;

        $form = new CustomForm(function (Player $player, ?array $data = null) use ($actualBet, $name) {
            if (!is_array($data) || !isset($data[1]) || !is_numeric($data[1]) || $data[1] <= 0) {
                return;
            }

            $bet = $data[1];
            $session = Session::get($player);

            if ($session->data["money"] < $bet) {
                $player->sendMessage(Util::PREFIX . "Vous ne possédez pas assez de pièces pour miser " . $bet . " à la lotterie");
                return;
            }

            self::$bets[$name] = $bet;
            $session->addValue("money", $bet, true);

            $player->sendMessage(Util::PREFIX . "Vous venez de miser §q" . $bet . " §fpièce(s) à la lotterie");
        });

        $form->setTitle("Lotterie");

        $totalBets = self::getTotalBets();
        $betCount = count(self::$bets);

        $form->addLabel("§l§q» §r§aInformations de la lotterie §l§q«§r\n\n§fNombre de mise(s)§8: §a" . $betCount . "\n§fSomme de(s) mise(s)§8: §a" . $totalBets . " pièce(s)\n§fTemps restant§8: §a" . LotteryTask::formatLotteryRemainingTime() . "\n\n§fVotre mise actuelle§8: §a" . $actualBet . " pièce(s)\n§fVos pièce(s)§8: §a" . $session->data["money"] . " pièce(s)");
        $form->addSlider("Mise", 1, 25000, default: ($actualBet === 0 ? 1 : $actualBet));

        $player->sendForm($form);
    }

    private function openActualBetsForm(Player $player): void
    {
        $form = new SimpleForm(null);

        $form->setTitle("Mises de la lotterie");

        $totalBets = self::getTotalBets();
        $betCount = count(self::$bets);
        $hasBet = $totalBets > 0;

        if ($hasBet) {
            $form->setContent(Util::PREFIX . "Nombre de mise(s)§8: §q" . $betCount . "\n" . Util::PREFIX  . "§fSomme de(s) mise(s)§8: §q" . $totalBets);

            foreach (self::$bets as $name => $bet) {
                $form->addButton("§8" . str_replace("_", " ", $name) . "\n§8" . Util::formatNumberWithSuffix($bet));
            }
        } else {
            $form->setContent(Util::PREFIX . "§cAucune mise n'a été faite pour le moment");
        }

        $player->sendForm($form);
    }

    private function openCancelBetForm(Player $player): void
    {
        $name = Util::getUnderscoredName($player);
        $actualBet = self::$bets[$name];

        $form = new SimpleForm(function (Player $player, ?int $data = null) use ($actualBet, $name) {
            if ($data !== 0) {
                return;
            }

            $session = Session::get($player);
            $session->addValue("money", $actualBet);
            unset(self::$bets[$name]);

            $player->sendMessage(Util::PREFIX . "Vous venez d'annuler votre mise de §q" . $actualBet . " §fpièce(s) à la lotterie");
        });

        $form->setTitle("Lotterie");
        $form->setContent(Util::PREFIX . "Souhaitez-vous réellement annuler votre mise de §q" . $actualBet . " §fpièce(s) ?");
        $form->addButton("Oui\n§q" . Util::formatNumberWithSuffix($actualBet));
        $form->addButton("Non");

        $player->sendForm($form);
    }

    public static function getTotalBets(): int
    {
        return array_sum(self::$bets);
    }

    protected function prepare(): void
    {
    }
}