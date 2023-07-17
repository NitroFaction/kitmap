<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\discord\Discord;
use Kitmap\handler\discord\EmbedBuilder;
use Kitmap\handler\Rank;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Unban extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "unban",
            "Permet de débannir les joueurs banni"
        );

        $this->setPermissions([Rank::GROUP_STAFF]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $username = strtolower($args["joueur"]);

        if ($sender instanceof Player && Session::get($sender)->data["rank"] === "guide") {
            $sender->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de faire cela");
            return;
        }

        if (!isset(Cache::$players["upper_name"][$username])) {
            if ($username === "all" && $sender->getName() === "MaXoooZ") {
                Cache::$bans = [];
                $sender->sendMessage(Util::PREFIX . "Vous venez de débannir tous les joueurs du serveur");
                return;
            }

            $sender->sendMessage(Util::PREFIX . "Vous venez de supprimer la valeur §e" . $username . " §fde la liste des bans");
            Main::getInstance()->getServer()->getNetwork()->unblockAddress($username);

            unset(Cache::$bans[$username]);
            return;
        }

        $file = Util::getFile("data/players/" . $username);
        $data = $file->getAll();

        unset(Cache::$bans[$username]);

        foreach (Cache::$config["saves"] as $column) {
            foreach ($data[$column] as $datum) {
                unset(Cache::$bans[$datum]);
                Main::getInstance()->getServer()->getNetwork()->unblockAddress($datum);
            }
        }

        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le staff §e" . $sender->getName() . " §fvient de débannir le joueur §e" . $username);

        $embed = new EmbedBuilder();
        $embed->setDescription("**Unban**\n\n**Joueur**\n" . $username . "\n\n*Dé-Banni par le staff: " . $sender->getName() . "*");
        $embed->setColor(5635925);
        Discord::send($embed, Cache::$config["sanction-webhook"]);
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("joueur"));
    }
}