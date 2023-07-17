<?php /** @noinspection PhpUnused */

namespace NCore\command\staff\sanction;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TargetArgument;
use CortexPE\Commando\BaseCommand;
use NCore\Base;
use NCore\handler\Cache;
use NCore\handler\SanctionAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Ban extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "ban",
            "Permet de bannir les tricheurs ou autre"
        );

        $this->setPermission("staff.group");
    }

    public static function checkBan(PlayerJoinEvent $event): bool
    {
        $player = $event->getPlayer();
        $session = Session::get($player);

        $name = strtolower($player->getName());
        $value = null;

        foreach (Cache::$config["saves"] as $column) {
            foreach ($session->data["player"][$column] as $datum) {
                if (isset(Cache::$bans[$datum])) {
                    $value = $datum;
                }
            }
        }

        if (isset(Cache::$bans[$name])) {
            $value = $name;
        }

        if (is_null($value)) {
            return false;
        }

        $data = Cache::$bans[$value];
        $time = $data[1];

        if ($time > time()) {
            $time = SanctionAPI::format($time - time());

            $staff = $data[0];
            $reason = $data[2];

            Base::getInstance()->getServer()->getNetwork()->blockAddress($player->getNetworkSession()->getIp(), 600);
            $player->kick("§fVous êtes banni de nitrofaction.\n\n§fTemps restant: §e" . $time . "\n§fRaison: §e" . $reason . "\n§fStaff: §e" . $staff);

            return true;
        } else {
            return false;
        }
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $username = strtolower($args["joueur"]);

        if ($sender instanceof Player && Session::get($sender)->data["player"]["rank"] === "guide") {
            $sender->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de faire cela");
        } else if (!isset(Cache::$players["upper_name"][$username])) {
            $sender->sendMessage(Util::PREFIX . "Ce joueur ne s'est jamais connecté au serveur (verifiez bien les caractères)");
        } else if ($sender instanceof Player) {
            SanctionAPI::sanctionForm($sender, $username, "ban");
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetArgument("joueur"));
        $this->registerArgument(0, new RawStringArgument("joueur"));
    }
}