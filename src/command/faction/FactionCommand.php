<?php

namespace Kitmap\command\faction;

use CortexPE\Commando\BaseSubCommand;
use Kitmap\handler\Faction as Api;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

abstract class FactionCommand extends BaseSubCommand
{
    protected bool $requiresPlayer = true;
    protected bool $requiresFaction = true;

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player && $this->requiresPlayer) {
            return;
        }

        if ($this->requiresFaction && $this->requiresPlayer) {
            $permission = Api::hasPermission($sender, strtolower($this->getName()));

            if (is_null($permission)) {
                $sender->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction");
                return;
            } elseif (!$permission) {
                $sender->sendMessage(Util::PREFIX . "Vous ne possèdez pas les permissions necessaire dans votre faction pour faire cela");
                return;
            }
        }

        if ($this->requiresPlayer && $sender instanceof Player) {
            $session = Session::get($sender);
            $faction = $session->data["faction"];

            $this->onNormalRun($sender, $session, $faction, $args);
        } else {
            $this->onBasicRun($sender, $args);
        }
    }

    public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args): void
    {
    }

    public function onBasicRun(CommandSender $sender, array $args): void
    {
    }

    protected function prepare(): void
    {
    }
}