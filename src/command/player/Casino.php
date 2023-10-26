<?php /* @noinspection PhpUnused */

namespace Kitmap\command\player;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Casino as Api;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Casino extends BaseCommand
{

    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "casino",
            "Jouer à un jeu du casino"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->inCooldown("combat")) {
                $sender->sendMessage(Util::PREFIX . "Cette commande est interdite en combat");
                return;
            }

            $money = $session->data["money"];

            if ($money < 10000) {
                $sender->sendMessage(Util::PREFIX . "Vous devez possédez au moins 10k pièces pour miser au casino");
                return;
            }

            $game = $args["jeu"] ?? null;
            $form = !is_null($game) ? Api::openCasinoGameForm($game) : Api::openCasinoForm();

            $sender->sendForm($form);
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("jeu", ["dragon-tower", "mines", "roulette"], true));
    }

}
