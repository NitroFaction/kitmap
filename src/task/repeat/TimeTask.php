<?php

namespace Kitmap\task\repeat;

use Kitmap\handler\Cache;
use Kitmap\Util;

class TimeTask extends BaseTask
{
    private int $tick = 0;

    public function onRun(): void
    {
        $this->tick++;

        foreach (Cache::$config["interval"] as $ticks => $commands) {
            if ($this->tick % intval($ticks) == 0) {
                array_map(fn($c) => Util::executeCommand($c), explode(":", $commands));
            }
        }

        $time = date("H:i");

        if (isset(Cache::$config["planning"][$time])) {
            array_map(fn($c) => Util::executeCommand($c), explode(":", Cache::$config["planning"][$time]));
        }

        if (($h = intval(explode(":", $time)[0])) >= 12 && $h <= 24) {
            if (intval(explode(":", $time)[1]) === 0 && ($h - 13) % 2 === 0) {
                Util::executeCommand("nexus start");
            } elseif (intval(explode(":", $time)[1]) === 30) {
                Util::executeCommand("koth start");
            } elseif (intval(explode(":", $time)[1]) === 45) {
                Util::executeCommand("kothpoints start");
            }
        }
    }
}