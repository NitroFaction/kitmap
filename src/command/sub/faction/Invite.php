<?php

namespace NCore\command\sub\faction;

use CortexPE\Commando\args\TargetArgument;
use CortexPE\Commando\BaseSubCommand;
use NCore\Base;
use NCore\handler\Cache;
use NCore\handler\FactionAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class Invite extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "invite", "Inviter un joueur dans sa faction", ["invit", "add"]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);
            $faction = $session->data["player"]["faction"];

            $target = Base::getInstance()->getServer()->getPlayerByPrefix($args["joueur"]);
            $permission = FactionAPI::hasPermission($sender, $this->getName());

            if (is_null($permission)) {
                $sender->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction");
                return;
            } else if (!$permission) {
                $sender->sendMessage(Util::PREFIX . "Vous ne possèdez pas les permissions necessaire dans votre faction pour faire cela");
                return;
            } else if (count(FactionAPI::getFactionMembers($faction, false)) >= 20) {
                $sender->sendMessage(Util::PREFIX . "Votre faction ne peut pas comporter plus de 20 joueurs");
                return;
            }

            if ($target instanceof Player) {
                $targetSession = Session::get($target);

                if (FactionAPI::hasFaction($target)) {
                    $sender->sendMessage(Util::PREFIX . "Le joueur indiqué est déjà dans une faction");
                    return;
                } else if (!in_array($faction, $targetSession->data["player"]["invite"])) {
                    $targetSession->data["player"]["invite"][] = $faction;
                }

                $target->sendMessage(Util::PREFIX . "Vous avez été invité à rejoindre la faction §e" . FactionAPI::getFactionUpperName($faction) . "\n§f/f accept §e" . $faction . " §fpour accepter l'invitation");

                Cache::$factions[$faction]["logs"][time()] = "§e" . $sender->getName() . " §finvite §e" . $target->getName();
                FactionAPI::broadcastMessage($faction, "§e[§fF§e] §fLe joueur §e" . $target->getName() . " §fvient d'être invité dans votre faction");
            } else {
                $sender->sendMessage(Util::PREFIX . "Le joueur indiqué n'est pas connecté sur le serveur");
            }
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetArgument("joueur"));
    }
}