<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player;

use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\task\async\VoteRequestTask;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Vote extends BaseCommand
{
    private static string $key = "jo4yngTLLYUykNvdlbScwsxOp7ouOVVu5c";

    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "vote",
            "Vote sur le serveur pour récuperer des récompenses"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public static function getResult(string $name, string $type, ?int $result): void
    {
        $player = Main::getInstance()->getServer()->getPlayerExact($name);

        if (!$player instanceof Player) {
            return;
        }

        if ($type === "object") {
            if ($result === 1) {
                self::sendPlayer($player, "action");
            } else {
                $message = match ($result) {
                    0 => "Vous n'avez toujours pas voté sur le serveur, rendez vous sur §ehttps://nitrofaction.fr/vote§f, pour pouvoir voté !",
                    default => "Vous avez déjà voté dans les 24 dernières heures"
                };

                $player->sendMessage(Util::PREFIX . $message);
            }
        } else if ($type === "action") {
            if ($result === 1) {
                self::getVoted($player);
            } else {
                $player->sendMessage(Util::PREFIX . "Vous avez déjà voté dans les 24 dernières heures");
            }
        }
    }

    public static function sendPlayer(Player $player, string $type = "object"): void
    {
        $user = str_replace(" ", "", $player->getName());
        $api = "https://minecraftpocket-servers.com/api/?";

        $query = match ($type) {
            "object" => "object=votes&element=claim&key=" . self::$key . "&username=" . $user,
            "action" => "action=post&object=votes&element=claim&key=" . self::$key . "&username=" . $user,
            default => null
        };

        if (!is_null($query)) {
            Main::getInstance()->getServer()->getAsyncPool()->submitTask(new VoteRequestTask($user, $type, $api . $query));
        }
    }

    public static function getVoted(Player $player): void
    {
        Cache::$data["voteparty"] += 1;

        if (intval(Cache::$data["voteparty"]) >= 100) {
            $packs = mt_rand(2, 4);

            foreach (Main::getInstance()->getServer()->getOnlinePlayers() as $target) {
                Session::get($target)->addValue("pack", $packs);
                $target->sendTitle("§eVoteParty !", "§fVos récompenses vous ont été données");
            }

            Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le voteparty est arrivé à son maximum ! Vous venez tous de recevoir.... §e" . $packs . " pack(s) §f! Profitez bien !!");
            Cache::$data["voteparty"] = 0;
        }

        Session::get($player)->addValue("pack", 2);

        $player->sendMessage(Util::PREFIX . "Vous venez de recevoir §e2 pack §fcar vous avez voté sur le serveur !");
        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le joueur §e" . $player->getDisplayName() . " §fvient de recevoir §e2 pack §fcar il a voté sur §ehttps://nitrofaction.fr/vote");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->inCooldown("vote")) {
                $seconds = $session->getCooldownData("vote")[0] - time();
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez actuellement pas faire de /vote, merci d'attendre §e" . $seconds . " §fsecondes !");
            }

            self::sendPlayer($sender);
            $session->setCooldown("vote", 60);
        }
    }

    protected function prepare(): void
    {
    }
}