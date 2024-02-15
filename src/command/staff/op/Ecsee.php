<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseCommand;
use Element\player\InvSeePlayerList;
use Kitmap\handler\Cache;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\GameMode;
use pocketmine\player\Player;

class Ecsee extends BaseCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "ecsee",
            "Accède à l'enderchest d'un joueur connecté"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        return;

        if ($sender instanceof Player) {
            if ($sender->getGamemode() === GameMode::SPECTATOR()) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas ecsee en spectateur");
                return;
            } else if (Session::get($sender)->data["staff_mod"][0]) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas ecsee en staffmod");
                return;
            }

            /** @noinspection PhpDeprecationInspection */
            if (($player = Main::getInstance()->getServer()->getPlayerByPrefix($args["joueur"])) instanceof Player) {
                $player = $player->getName();
            } else {
                $player = strtolower($args["joueur"]);

                if (!isset(Cache::$players["upper_name"][$player])) {
                    $sender->sendMessage(Util::PREFIX . "Ce joueur ne s'est jamais connecté au serveur (verifiez bien les caractères)");
                    return;
                }
            }

            $player = InvSeePlayerList::getInstance()->getOrCreate($player);
            $player->getEnderChestInventoryMenu()->send($sender);
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(false, "joueur"));
        $this->registerArgument(0, new RawStringArgument("joueur"));
    }
}