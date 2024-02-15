<?php

namespace Kitmap\entity;

use Kitmap\command\player\Gambling;
use Kitmap\handler\Cache;
use Kitmap\handler\Faction;
use Kitmap\task\repeat\child\DominationTask;
use Kitmap\task\repeat\child\GamblingTask;
use Kitmap\task\repeat\child\KothTask;
use Kitmap\task\repeat\child\OutpostTask;
use Kitmap\Util;

class DefaultFloatingText extends FloatingText
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
                            return Util::PREFIX . "Domination §9§l«\n§fAucun event §9domination §fn'est en cours";
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
                            default => "§fLa faction §9" . $actual . " §fcontrôle le point"
                        };

                        return Util::PREFIX . "Point " . $zone . " §9§l«\n" . $actual . "\n§fStatus du point: " . $status;
                    }
                }
                break;
            case "koth":
                if (is_numeric(KothTask::$currentKoth)) {
                    $player = KothTask::$currentPlayer;
                    $player = is_null($player) ? "Aucun joueur" : $player;

                    $remaining = Util::formatDurationFromSeconds(KothTask::$currentKoth);
                    return Util::PREFIX . "Koth §9§l«\n§9" . $player . " §fcontrôle le koth actuellement\n§fTemps restant : §9" . $remaining;
                } else {
                    return Util::PREFIX . "Koth §9§l«\n§fAucun event §9koth §fn'est en cours";
                }
            case "outpost":
                if (!is_null(Cache::$data["outpost"])) {
                    $remaining = Util::formatDurationFromSeconds(OutpostTask::$nextReward);
                    $faction = Faction::getFactionUpperName(Cache::$data["outpost"]);

                    return Util::PREFIX . "Outpost §9§l«\n§fLa faction §9" . $faction . " §fcontrôle l'outpost\n§fRécompense dans §9" . $remaining . "\n§fPlus controlé dans §9" . OutpostTask::$currentOutpost . " §fsecondes";
                } else {
                    $remaining = Util::formatDurationFromSeconds(OutpostTask::$currentOutpost);
                    return Util::PREFIX . "Outpost §9§l«\n§9Aucune §ffaction ne contrôle l'outpost\n§fOutpost contrôlé dans §9" . $remaining;
                }
            case "gambling":
                if (GamblingTask::$currently) {
                    return Util::PREFIX . "Gambling §9§l«\nUn gambling est actuellement en cours depuis §9" . Util::formatDurationFromSeconds(GamblingTask::$since, 1) . "\nLe gambling actuel oppose §9" . GamblingTask::$players[0] . " §fet §9" . GamblingTask::$players[1] . "\n\n§9" . count(Gambling::$gamblings) . " §fautre(s) §9gambling(s) §fsont en attente d'adversaire";
                } else {
                    return Util::PREFIX . "Gambling §9§l«\nAucun gambling n'est actuellement en cours\n§9" . count(Gambling::$gamblings) . " gambling(s) §fsont en attente d'adversaire\nPour rejoindre un gambling utilisez la commande §9/gambling";
                }
            case "money-zone":
                $this->period = null;
                return Util::PREFIX . "Zone Money §9§l«\nReste ici et gagne §950 §fpièces toutes les §93 §fsecondes\n§fATTENTION ! Tu dois être §9seul §fsur la platforme";
            case "blocks":
                $this->period = null;
                return Util::PREFIX . "Salle des blocs §9§l«\nBienvenue dans la salle des §9blocs §f!\n§fTous les blocs que vous §9cassez §fsont mis\n§fdans votre inventaire en échange de §915 §fpièces par bloc en illimité";
        }

        if ($name[0] === "#") {
            $text = substr($name, 1);
        } else {
            $text = "§r   \n  " . Util::stringToUnicode($name) . "  \n§r   ";
        }

        $this->period = null;
        return $text;
    }
}