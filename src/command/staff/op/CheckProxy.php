<?php

namespace Kitmap\command\staff\op;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;

class CheckProxy extends BaseCommand
{

    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "checkproxy",
            "Détecter si un joueur est suspecté d'utiliser un proxy"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $target = strtolower($args["joueur"]);

        if (!isset(Cache::$players["upper_name"][$target])) {
            $sender->sendMessage(Util::PREFIX . "Ce joueur ne s'est jamais connecté au serveur (verifiez bien les caractères)");
            return;
        }

        $allCidDid = $this->getAllCidDidByName($target);
        $bar = "§l§8-----------------------";

        $sender->sendMessage($bar);
        $sender->sendMessage(Util::PREFIX . "Résultats du test proxy de §9" . $target);

        $isSuspect = false;

        foreach ($allCidDid as $column => $count) {
            $sender->sendMessage("§l§9| §r§f" . strtoupper($column) . " §8- §f" . $count);
            if ($count >= 10) {
                $isSuspect = true;
            }
        }

        $sender->sendMessage("§l§9| §r§fVerdict §8- " . ($isSuspect ? "§aSUSPECTÉ" : "§cNON SUSPECTÉ"));
        $sender->sendMessage($bar);
    }


    private function getAllCidDidByName(string $name): array
    {
        $result = [];
        $file = Util::getFile("data/players/" . $name);

        $values = $file->get("did", []);
        $result["did"] = count($values);

        return $result;
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(false, "joueur"));
        $this->registerArgument(0, new RawStringArgument("joueur"));
    }
}
