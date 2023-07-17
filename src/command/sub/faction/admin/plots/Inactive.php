<?php

namespace NCore\command\sub\faction\admin\plots;

use CortexPE\Commando\BaseSubCommand;
use NCore\Base;
use NCore\handler\Cache;
use NCore\Util;
use pocketmine\command\CommandSender;

class Inactive extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "inactive", "");
        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $count = 0;

        foreach (Cache::$factions as $key => $faction) {
            $activity = array_keys($faction["activity"]);

            $date1 = date("d-m", time());
            $date2 = date("d-m", time() - 86400);
            $date3 = date("d-m", time() - (86400 * 2));

            if (count(array_intersect($activity, [$date1, $date2, $date3])) === 0) {
                $plot = $faction["claim"];

                if (!is_null($plot) && Cache::$plots[$plot]["faction"] === $key) {
                    $sender->sendMessage(Util::PREFIX . "Plot: §e" . $plot . " §fréinitialisé !");

                    Cache::$factions[$key]["claim"] = null;
                    Cache::$plots[$plot]["faction"] = null;

                    Reset::resetPlot($plot);
                    $count++;
                }
            }
        }

        $sender->sendMessage(Util::PREFIX . "§e" . $count . " §fplots réinitialisé(s) !");
    }

    protected function prepare(): void
    {
    }
}