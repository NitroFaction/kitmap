<?php /** @noinspection PhpUnused */

namespace Kitmap\command\util;

use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\Rank;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Bienvenue extends BaseCommand
{
    public static array $alreadyWished = [];
    public static string $lastJoin = "";

    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "bienvenue",
            "Souhaite la bienvenue à un nouveau joueur"
        );

        $this->setAliases(["bvn"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if (Bienvenue::$lastJoin === "" || in_array($sender->getName(), Bienvenue::$alreadyWished)) {
                $sender->sendMessage(Util::PREFIX . "Vous avez déjà souhaité la bienvenue ou aucun nouveau joueur n'a rejoint le serveur dernièrement");
                return;
            }

            $target = Main::getInstance()->getServer()->getPlayerExact(Bienvenue::$lastJoin);

            $message = str_replace("{player}", Bienvenue::$lastJoin, Cache::$config["welcome-messages"][array_rand(Cache::$config["welcome-messages"])]);
            $message = Rank::setReplace(Rank::getRankValue(Rank::getRank($sender->getName()), "chat"), $sender, $message);

            if ($target instanceof Player) {
                $target->sendMessage($message);
            }

            Bienvenue::$alreadyWished[] = $sender->getName();
            $sender->sendMessage($message);

            $session->addValue("money", 500);
            $sender->sendMessage(Util::PREFIX . "Vous avez reçu §e500 §fpièces car vous avez souhaité la bienvenue de §e" . Bienvenue::$lastJoin);
        }
    }

    protected function prepare(): void
    {
    }
}