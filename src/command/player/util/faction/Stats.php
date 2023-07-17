<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\faction;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TargetArgument;
use CortexPE\Commando\BaseCommand;
use NCore\Base;
use NCore\handler\Cache;
use NCore\handler\FactionAPI;
use NCore\handler\SanctionAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Stats extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "stats",
            "Récupere ses informations ou celle d'une autre personne"
        );

        $this->setAliases(["info"]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $username = strtolower($args["joueur"] ?? $sender->getName());
            $target = Base::getInstance()->getServer()->getPlayerByPrefix($username);

            if (!isset(Cache::$players["upper_name"][$username])) {
                $sender->sendMessage(Util::PREFIX . "Ce joueur ne s'est jamais connecté au serveur (verifiez bien les caractères)");
                return;
            }

            $additional = [];

            if ($target instanceof Player) {
                $session = Session::get($target);
                FactionAPI::hasFaction($target);

                $additional[] = "§ePlatforme: §f" . Cache::$config["devices"][$target->getPlayerInfo()->getExtraData()["DeviceOS"] ?? 0];
                $additional[] = "§ePériphérique: §f" . Cache::$config["controls"][$target->getPlayerInfo()->getExtraData()["CurrentInputMode"] ?? 0];

                $session->data["player"]["played_time"] += time() - $session->data["play_time"];
                Cache::$players["played_time"][$username] = $session->data["player"]["played_time"];

                $session->data["play_time"] = time();
                $data = $session->data["player"];
            } else {
                $file = Util::getFile("players/" . $username);
                $data = $file->getAll();
            }

            $bar = "§l§8-----------------------";
            $playtime = SanctionAPI::format($data["played_time"]);

            $faction = $data["faction"];
            $faction = (is_null($faction)) ? "Aucune Faction" : FactionAPI::getFactionUpperName($faction);

            $sender->sendMessage($bar);
            $sender->sendMessage("§e[§f" . $faction . "§e] [§f" . ucfirst(strtolower($data["rank"])) . "§e] §f- §e" . $data["upper_name"]);
            $sender->sendMessage("§ePièces: §f" . $data["money"]);
            $sender->sendMessage("§eGemmes: §f" . $data["gem"]);
            $sender->sendMessage("§eKills: §f" . $data["kill"]);
            $sender->sendMessage("§eMorts: §f" . $data["death"]);
            $sender->sendMessage("§eKillstreak: §f" . $data["killstreak"]);
            $sender->sendMessage("§eTemps de jeu: §f" . $playtime);
            foreach ($additional as $value) {
                $sender->sendMessage($value);
            }
            $sender->sendMessage($bar);
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetArgument("joueur", true));
        $this->registerArgument(0, new RawStringArgument("joueur", true));
    }
}