<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util;

use CortexPE\Commando\BaseCommand;
use NCore\Base;
use NCore\handler\Cache;
use NCore\handler\RankAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
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
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if (Bienvenue::$lastJoin === "" || in_array($sender->getName(), Bienvenue::$alreadyWished)) {
                $sender->sendMessage(Util::PREFIX . "Vous avez déjà souhaité la bienvenue ou aucun nouveau joueur n'a rejoint le serveur dernièrement");
                return;
            }

            $target = Base::getInstance()->getServer()->getPlayerByPrefix(Bienvenue::$lastJoin);

            $message = str_replace("{player}", Bienvenue::$lastJoin, Cache::$config["welcome_messages"][array_rand(Cache::$config["welcome_messages"])]);
            $message = RankAPI::setReplace(RankAPI::getRankValue(RankAPI::getRank($sender->getName()), "chat"), $sender, $message);

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