<?php

namespace Kitmap\entity;

use Element\entity\FloatingText as FloatingTextEntity;
use Kitmap\command\player\Gambling;
use Kitmap\handler\Cache;
use Kitmap\handler\Faction;
use Kitmap\task\repeat\DominationTask;
use Kitmap\task\repeat\GamblingTask;
use Kitmap\task\repeat\KothTask;
use Kitmap\task\repeat\OutpostTask;
use Kitmap\Util;

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
                            return Util::PREFIX . "Domination §q§l«\n§fAucun event §qdomination §fn'est en cours";
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
                            default => "§fLa faction §q" . $actual . " §fcontrôle le point"
                        };

                        return Util::PREFIX . "Point " . $zone . " §q§l«\n" . $actual . "\n§fStatus du point: " . $status;
                    }
                }
                break;
            case "koth":
                if (is_numeric(KothTask::$currentKoth)) {
                    $player = KothTask::$currentPlayer;
                    $player = is_null($player) ? "Aucun joueur" : $player;

                    $remaining = Util::formatDurationFromSeconds(KothTask::$currentKoth);
                    return Util::PREFIX . "Koth §q§l«\n§q" . $player . " §fcontrôle le koth actuellement\n§fTemps restant : §q" . $remaining;
                } else {
                    return Util::PREFIX . "Koth §q§l«\n§fAucun event §qkoth §fn'est en cours";
                }
            case "outpost":
                if (!is_null(Cache::$data["outpost"])) {
                    $remaining = Util::formatDurationFromSeconds(OutpostTask::$nextReward);
                    $faction = Faction::getFactionUpperName(Cache::$data["outpost"]);

                    return Util::PREFIX . "Outpost §q§l«\n§fLa faction §q" . $faction . " §fcontrôle l'outpost\n§fRécompense dans §q" . $remaining . "\n§fPlus controlé dans §q" . OutpostTask::$currentOutpost . " §fsecondes";
                } else {
                    $remaining = Util::formatDurationFromSeconds(OutpostTask::$currentOutpost);
                    return Util::PREFIX . "Outpost §q§l«\n§qAucune §ffaction ne contrôle l'outpost\n§fOutpost contrôlé dans §q" . $remaining;
                }
            case "gambling":
                if (GamblingTask::$currently) {
                    return Util::PREFIX . "Gambling §q§l«\nUn gambling est actuellement en cours depuis §q" . Util::formatDurationFromSeconds(GamblingTask::$since, 1) . "\nLe gambling actuel oppose §q" . GamblingTask::$players[0] . " §fet §q" . GamblingTask::$players[1] . "\n\n§q" . count(Gambling::$gamblings) . " §fautre(s) §qgambling(s) §fsont en attente d'adversaire";
                } else {
                    return Util::PREFIX . "Gambling §q§l«\nAucun gambling n'est actuellement en cours\n§q" . count(Gambling::$gamblings) . " gambling(s) §fsont en attente d'adversaire\nPour rejoindre un gambling utilisez la commande §q/gambling";
                }
            case "money-zone":
                $this->period = null;
                return Util::PREFIX . "Zone Money §q§l«\nReste ici et gagne §q50 §fpièces toutes les §q3 §fsecondes\n§fATTENTION ! Tu dois être §qseul §fsur la platforme";
            case "blocks":
                $this->period = null;
                return Util::PREFIX . "Salle des blocs §q§l«\nBienvenue dans la salle des §qblocs §f!\n§fTous les blocs que vous §qcassez §fsont mis\n§fdans votre inventaire en échange de §q15 §fpièces par bloc en illimité";
        }

        $this->period = null;
        return "§r   \n  " . Util::stringToUnicode($name) . "  \n§r   ";
    }
}