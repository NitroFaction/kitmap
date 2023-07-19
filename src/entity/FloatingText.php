<?php

namespace Kitmap\entity;

use Kitmap\handler\Cache;
use Kitmap\handler\Faction;
use Kitmap\task\repeat\DominationTask;
use Kitmap\task\repeat\KothTask;
use Kitmap\task\repeat\OutpostTask;
use Kitmap\Util;
use MaXoooZ\Util\entity\FloatingText as FloatingTextEntity;

class FloatingText extends FloatingTextEntity
{
    protected function getPeriod(): ?int
    {
        return $this->period;
    }

    protected function getUpdate(): string
    {
        $floatings = Cache::$config["floatings"];

        $position = $this->getLocation();
        $text = $position->getX() . ":" . $position->getY() . ":" . $position->getZ() . ":" . $position->getWorld()->getFolderName();

        $name = $floatings[$text] ?? false;

        if (is_bool($name)) {
            return "";
        }

        switch ($name) {
            case "domination":
                foreach (array_keys(Cache::$config["domination"]) as $zone) {
                    if (DominationTask::insideZone($zone, $this->getPosition())) {
                        if (!DominationTask::$currentDomination) {
                            DominationTask::updateZoneBlocks($zone);
                            return Util::PREFIX . "Domination §e§l«\n§fAucun event §edomination §fn'est en cours";
                        }

                        $status = DominationTask::$zones[$zone][1][0] ?? "uncaptured";
                        DominationTask::updateZoneBlocks($zone, $status);

                        $status = match ($status) {
                            "captured" => "§aCapturé",
                            "uncaptured" => "§7Libre",
                            "contested" => "§cContesté"
                        };

                        $actual = DominationTask::$zones[$zone][0] ?? null;
                        $actual = is_null($actual) ? false : Faction::getFactionUpperName($actual);

                        if ($status === "§7Libre") {
                            $actual = false;
                        }

                        $actual = match (true) {
                            is_bool($actual) => "§fAucune faction contrôle le point",
                            default => "§fLa faction §e" . $actual . " §fcontrôle le point"
                        };

                        return Util::PREFIX . "Point " . $zone . " §e§l«\n" . $actual . "\n§fStatus du point: " . $status;
                    }
                }
                break;
            case "koth":
                if (is_numeric(KothTask::$currentKoth)) {
                    $player = KothTask::$currentPlayer;
                    $player = is_null($player) ? "Aucun joueur" : $player;

                    $remaining = Util::formatDurationFromSeconds(KothTask::$currentKoth);
                    return Util::PREFIX . "Koth §e§l«\n§e" . $player . " §fcontrôle le koth actuellement\n§fTemps restant : §e" . $remaining;
                } else {
                    return Util::PREFIX . "Koth §e§l«\n§fAucun event §ekoth §fn'est en cours";
                }
            case "outpost":
                if (!is_null(Cache::$data["outpost"])) {
                    $remaining = Util::formatDurationFromSeconds(OutpostTask::$nextReward);
                    $faction = Faction::getFactionUpperName(Cache::$data["outpost"]);

                    return Util::PREFIX . "Outpost §e§l«\n§fLa faction §e" . $faction . " §fcontrôle l'outpost\n§fRécompense dans §e" . $remaining . "\n§fPlus controlé dans §e" . OutpostTask::$currentOutpost . " §fsecondes";
                } else {
                    $remaining = Util::formatDurationFromSeconds(OutpostTask::$currentOutpost);
                    return Util::PREFIX . "Outpost §e§l«\n§eAucune §ffaction ne contrôle l'outpost\n§fOutpost contrôlé dans §e" . $remaining;
                }
            case "blocks":
                $this->period = null;
                return Util::PREFIX . "Salle des blocs §e§l«\nBienvenue dans la salle des §eblocs §f!\n§fTous les blocs que vous §ecassez §fsont mis\n§fdans votre inventaire pour §e0 §fpièces en illimité";
        }

        $this->period = null;
        return "§r   \n  " . Util::stringToUnicode($name) . "  \n§r   ";
    }
}