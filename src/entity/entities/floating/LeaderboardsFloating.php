<?php

namespace Kitmap\entity\entities\floating;

use Kitmap\command\player\Top;
use Kitmap\Util;

class LeaderboardsFloating extends FloatingTextEntity
{
    private int $currentCategory = 0;

    private array $category = [
        "kill",
        "money",
        "faction",
        "killstreak"
    ];

    protected function getPeriod(): int
    {
        return 600;
    }

    protected function getUpdate(): string
    {
        $this->currentCategory++;
        $i = 1;

        if (!isset($this->category[$this->currentCategory])) {
            $this->currentCategory = 0;
        }

        $top = $this->currentCategory === 2 ? Top::getFactionTopList() : Top::getPlayersTopList($this->category[$this->currentCategory]);

        $str = match ($this->currentCategory) {
            0 => "§l§fJoueurs avec le plus de §ekills",
            1 => "§l§fJoueurs avec le plus de §epièces",
            2 => "§l§fClassement des meilleures §efactions",
            3 => "§l§fJoueurs avec le plus gros §ekillstreak"
        };

        foreach (Util::arrayToMessage($top, 1, "§e{KEY} §8(§f{VALUE}§8)")[1] as $message) {
            $str .= "\n§7" . $i . ". " . $message;
            $i++;
        }
        return $str;
    }
}