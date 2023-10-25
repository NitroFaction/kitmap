<?php

namespace Kitmap\command\faction\subcommands;

use CortexPE\Commando\args\TargetArgument;
use Kitmap\command\faction\FactionCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\Faction;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Invite extends FactionCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "invite",
            "Inviter un joueur dans sa faction"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
        $this->setAliases(["invit", "add"]);
    }

    public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args): void
    {
        /** @noinspection PhpDeprecationInspection */
        $target = Main::getInstance()->getServer()->getPlayerByPrefix($args["joueur"]);

        if (count(Faction::getFactionMembers($faction, false)) >= 20) {
            $sender->sendMessage(Util::PREFIX . "Votre faction ne peut pas comporter plus de 20 joueurs");
            return;
        } else if (!$target instanceof Player) {
            $sender->sendMessage(Util::PREFIX . "Le joueur indiqué n'est pas connecté sur le serveur");
            return;
        }

        $targetSession = Session::get($target);

        if (Faction::hasFaction($target)) {
            $sender->sendMessage(Util::PREFIX . "Le joueur indiqué est déjà dans une faction");
            return;
        } else if (!in_array($faction, $targetSession->data["invite"])) {
            $targetSession->data["invite"][] = $faction;
        }

        $target->sendMessage(Util::PREFIX . "Vous avez été invité à rejoindre la faction §6" . Faction::getFactionUpperName($faction) . "\n§f/f accept §6" . $faction . " §fpour accepter l'invitation");

        Cache::$factions[$faction]["logs"][time()] = "§6" . $sender->getName() . " §finvite §6" . $target->getName();
        Faction::broadcastMessage($faction, "§6[§fF§6] §fLe joueur §6" . $target->getName() . " §fvient d'être invité dans votre faction");
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetArgument("joueur"));
    }
}