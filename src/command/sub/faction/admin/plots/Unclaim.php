<?php

namespace NCore\command\sub\faction\admin\plots;

use CortexPE\Commando\args\BooleanArgument;
use CortexPE\Commando\BaseSubCommand;
use NCore\Base;
use NCore\handler\Cache;
use NCore\handler\FactionAPI;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class Unclaim extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "unclaim", "");
        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $plot = FactionAPI::inPlot($sender->getPosition()->getX(), $sender->getPosition()->getZ());

            if ($plot[1] === "Nature" || $plot[1] === "Aucune Faction") {
                return;
            }

            if (isset(Cache::$factions[$plot[1]])) {
                Cache::$factions[$plot[1]]["claim"] = null;
            }

            Cache::$plots[$plot[2]]["faction"] = null;

            $sender->sendMessage(Util::PREFIX . "Le claim §e" . $plot[1] . " §fa été unclaim");

            if ($args["reset"] ?? false) {
                Reset::resetPlot($plot[2]);
            }
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new BooleanArgument("reset", true));
    }
}