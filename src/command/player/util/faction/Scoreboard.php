<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\faction;

use CortexPE\Commando\BaseCommand;
use NCore\handler\ScoreFactory;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Scoreboard extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "scoreboard",
            "Active ou désactive le scoreboard"
        );
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->data["player"]["scoreboard"]) {
                $session->data["player"]["scoreboard"] = false;
                $sender->sendMessage(Util::PREFIX . "Vous ne verrez plus le scoreboard");

                ScoreFactory::removeScore($sender);
            } else {
                $session->data["player"]["scoreboard"] = true;
                $sender->sendMessage(Util::PREFIX . "Vous voyez desormais le scoreboard");

                ScoreFactory::updateScoreboard($sender);
            }
        }
    }

    protected function prepare(): void
    {
    }
}