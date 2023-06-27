<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\tool;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TargetArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;

class Alias extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "alias",
            "Permet de voir tous les comptes d'un joueur"
        );

        $this->setPermission("staff.group");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $target = strtolower($args["joueur"]);

        if (!isset(Cache::$players["upper_name"][$target])) {
            $sender->sendMessage(Util::PREFIX . "Ce joueur ne s'est jamais connecté au serveur (verifiez bien les caractères)");
            return;
        }

        $alias = $this->getPlayerAliasByName($target);
        $bar = "§l§8-----------------------";

        if (count($alias) === 0) {
            $sender->sendMessage(Util::PREFIX . "Le joueur §e" . $target . " §fne possède aucun double compte lié à son ip, did etc..");
            return;
        }

        $sender->sendMessage($bar);
        $sender->sendMessage(Util::PREFIX . "Liste de compte lié au compte §e" . $target);

        foreach ($alias as $username) {
            $sender->sendMessage("§f- §e" . $username);
        }

        $sender->sendMessage($bar);
    }

    private function getPlayerAliasByName(string $name): array
    {
        $file = Util::getFile("players/" . $name);
        $result = [];

        foreach (Cache::$config["saves"] as $column) {
            $ip = $file->get($column, []);

            foreach (Cache::$players[$column] as $key => $value) {
                $similar = array_intersect_assoc($value, $ip);

                if (count($similar) > 0 && $key !== $name) {
                    $result[] = $key . " §f- Depuis son §e" . $column;
                }
            }
        }
        return $result;
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetArgument("joueur"));
        $this->registerArgument(0, new RawStringArgument("joueur"));
    }
}