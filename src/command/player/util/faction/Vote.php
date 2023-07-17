<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\faction;

use CortexPE\Commando\BaseCommand;
use NCore\Base;
use NCore\handler\Cache;
use NCore\Session;
use NCore\task\async\VoteRequestTask;
use NCore\Util;
use pocketmine\command\CommandSender;
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
    }

    public static function getResult(string $name, string $type, ?int $result): bool
    {
        $player = Base::getInstance()->getServer()->getPlayerByPrefix($name);

        if ($player instanceof Player) {
            switch ($type) {
                case "object":
                    switch ($result) {
                        case 0:
                            $player->sendMessage(Util::PREFIX . "Vous n'avez toujours pas voté sur le serveur, rendez vous sur §ehttps://nitrofaction.fr/vote§f, pour pouvoir voté !");
                            break;
                        case 1:
                            self::sendPlayer($player, "action");
                            break;
                        case 2:
                            $player->sendMessage(Util::PREFIX . "Vous avez déjà voté dans les 24 dernières heures");
                            break;
                    }
                    break;

                case "action":
                    switch ($result) {
                        case 0:
                            $player->sendMessage(Util::PREFIX . "Vous avez déjà voté dans les 24 dernières heures");
                            break;
                        case 1:
                            self::getVoted($player);
                            break;
                    }
                    break;
            }
            return true;
        }
        return false;
    }

    public static function sendPlayer(Player $player, string $type = "object"): bool
    {
        $user = str_replace(" ", "", $player->getName());
        $api = "https://minecraftpocket-servers.com/api/?";

        switch ($type) {
            case "object":
                $query = "object=votes&element=claim&key=" . self::$key . "&username=" . $user;
                break;
            case "action":
                $query = "action=post&object=votes&element=claim&key=" . self::$key . "&username=" . $user;
                break;
            default:
                return false;
        }

        Base::getInstance()->getServer()->getAsyncPool()->submitTask(new VoteRequestTask($user, $type, $api . $query));
        return true;
    }

    public static function getVoted(Player $player): void
    {
        Cache::$dynamic["voteparty"] += 1;

        if (intval(Cache::$dynamic["voteparty"]) >= 100) {
            $packs = mt_rand(2, 4);

            foreach (Base::getInstance()->getServer()->getOnlinePlayers() as $target) {
                Session::get($target)->addValue("pack", $packs);
                $target->sendTitle("§eVoteParty !", "§fVos récompenses vous ont été données");
            }

            Base::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le voteparty est arrivé à son maximum ! Vous venez tous de recevoir.... §e" . $packs . " pack(s) §f! Profitez bien !!");
            Cache::$dynamic["voteparty"] = 0;
        }

        Session::get($player)->addValue("pack", 2);

        $player->sendMessage(Util::PREFIX . "Vous venez de recevoir §e2 pack §fcar vous avez voté sur le serveur !");
        Base::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le joueur §e" . $player->getDisplayName() . " §fvient de recevoir §e2 pack §fcar il a voté sur §ehttps://nitrofaction.fr/vote");
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