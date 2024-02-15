<?php

/**
 * @noinspection PhpUnused
 * @noinspection PhpDeprecationInspection
 */

namespace Kitmap\command\player;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Bounty extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "bounty",
            "Connaître la valeur de la prime d'un joueur"
        );

        $this->setAliases(["prime"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $username = strtolower($args["joueur"] ?? $sender->getName());
            $target = Main::getInstance()->getServer()->getPlayerByPrefix($username);

            if (!isset(Cache::$players["upper_name"][$username])) {
                $sender->sendMessage(Util::PREFIX . "Ce joueur ne s'est jamais connecté au serveur (verifiez bien les caractères)");
                return;
            }

            $data = $target instanceof Player ? Session::get($target)->data : Util::getFile("data/players/" . $username)->getAll();

            $upperName = $data["upper_name"];
            $bounty = $data["bounty"];

            if ($username === strtolower($sender->getName())) {
                if ($bounty > 0) {
                    $sender->sendMessage(Util::PREFIX . "Votre prime s'élève à §9" . $bounty . " pièce(s) §f!");
                } else {
                    $sender->sendMessage(Util::PREFIX . "Vous ne possèdez pas de prime");
                }
            } else {
                if ($bounty > 0) {
                    $sender->sendMessage(Util::PREFIX . "La prime de §9" . $upperName . " §fs'élève à §9" . $bounty . " pièce(s) §f!");
                } else {
                    $sender->sendMessage(Util::PREFIX . $upperName . " ne possède pas de prime");
                }
            }
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(true, "joueur"));
        $this->registerArgument(0, new RawStringArgument("joueur", true));
    }
}