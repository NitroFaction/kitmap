<?php

namespace NCore\command\sub\faction\admin;

use CortexPE\Commando\BaseSubCommand;
use NCore\Base;
use NCore\command\sub\faction\admin\plots\Cancel;
use NCore\command\sub\faction\admin\plots\Create;
use NCore\command\sub\faction\admin\plots\Here;
use NCore\command\sub\faction\admin\plots\Inactive;
use NCore\command\sub\faction\admin\plots\Remove;
use NCore\command\sub\faction\admin\plots\Reset;
use NCore\command\sub\faction\admin\plots\Teleport;
use NCore\command\sub\faction\admin\plots\Unclaim;
use NCore\handler\Cache;
use NCore\handler\FactionAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class Plots extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "plots", "Créer un nouveau plots pour que des factions puisse claim");
        $this->setPermission("pocketmine.group.operator");
    }

    public static function confirm(Player $player): void
    {
        $session = Session::get($player);
        $id = 1;

        while (isset(Cache::$plots[$id])) {
            $id++;
        }

        $plot = $session->data["player"]["plot"];

        $pos1 = $plot[1];
        $pos2 = $plot[2];

        if (is_null($pos1) || is_null($pos2)) {
            $player->sendMessage(Util::PREFIX . "Vous n'avez pas selectionné les deux positions");
            return;
        }

        list($minX, $minZ) = explode(":", $pos1);
        list($maxX, $maxZ) = explode(":", $pos2);

        $minX = intval($minX);
        $minZ = intval($minZ);

        $maxX = intval($maxX);
        $maxZ = intval($maxZ);

        $posXmin = min($minX, $maxX);
        $posXmax = max($minX, $maxX) + 0.7;

        $posZmin = min($minZ, $maxZ);
        $posZmax = max($minZ, $maxZ) + 0.7;

        if (FactionAPI::inPlot($posXmin, $posZmin)[0] || FactionAPI::inPlot($posXmax, $posZmax)[0]) {
            $player->sendMessage(Util::PREFIX . "Le plot ne peut pas se disposer dans un autre plot");
            Cancel::cancel($player);
            return;
        }

        $block = $player->getWorld()->getBlockAt($minX, 63, $minZ);

        Cache::$plots[$id]["min_x"] = $posXmin;
        Cache::$plots[$id]["min_z"] = $posZmin;

        Cache::$plots[$id]["max_x"] = $posXmax;
        Cache::$plots[$id]["max_z"] = $posZmax;

        Cache::$plots[$id]["faction"] = null;
        Cache::$plots[$id]["block"] = $block->getId() . ":" . $block->getMeta();

        $player->sendMessage(Util::PREFIX . "Vous venez de créer un nouveau plot (§e" . $id . "§f)");
        Cancel::cancel($player);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
    }

    protected function prepare(): void
    {
        $this->registerSubCommand(new Cancel());
        $this->registerSubCommand(new Create());
        $this->registerSubCommand(new Remove());
        $this->registerSubCommand(new Here());
        $this->registerSubCommand(new Inactive());
        $this->registerSubCommand(new Reset());
        $this->registerSubCommand(new Teleport());
        $this->registerSubCommand(new Unclaim());
    }
}