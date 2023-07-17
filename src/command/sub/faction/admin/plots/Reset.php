<?php

namespace NCore\command\sub\faction\admin\plots;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseSubCommand;
use NCore\Base;
use NCore\handler\Cache;
use NCore\handler\FactionAPI;
use NCore\Util;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\tile\Tile;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\World;

class Reset extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "reset", "");
        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $id = $args["id"] ?? -1;

        if ($id === "all") {
            foreach (Cache::$plots as $id => $value) {
                $sender->sendMessage(Util::PREFIX . "Le plot: §e" . $id . " §fcommence sa réinitialisation");
                Reset::resetPlot($id);
                $sender->sendMessage(Util::PREFIX . "Le plot: §e" . $id . " §fa fini sa réinitialisation");
            }

            $sender->sendMessage(Util::PREFIX . "Tous les plots ont été réinitialisés");
            return;
        }

        if ($id === -1 && $sender instanceof Player) {
            $id = FactionAPI::inPlot($sender->getPosition()->getX(), $sender->getPosition()->getZ())[2];
        }

        if (isset(Cache::$plots[$id])) {
            Reset::resetPlot($id);
            $sender->sendMessage(Util::PREFIX . "Le plot §e" . $id . " §fa été réinitialisé");
        } else {
            $sender->sendMessage(Util::PREFIX . "Le plot §e" . $id . " §fn'existe pas");
        }
    }

    public static function resetPlot(int $id): void
    {
        $value = Cache::$plots[$id];

        $minX = min($value["min_x"], $value["max_x"]);
        $minZ = min($value["min_z"], $value["min_z"]);

        $maxX = max($value["max_x"], $value["max_x"]);
        $maxZ = max($value["max_z"], $value["max_z"]);

        list($bid, $meta) = explode(":", $value["block"]);

        $world = Base::getInstance()->getServer()->getWorldManager()->getDefaultWorld();

        if (!$world instanceof World) {
            return;
        }

        for ($x = $minX; $x <= $maxX; $x++) {
            for ($y = 1; $y <= 255; $y++) {
                for ($z = $minZ; $z <= $maxZ; $z++) {
                    $tile = $world->getTileAt($x, $y, $z);

                    if ($tile instanceof Tile) {
                        $world->removeTile($tile);
                    }

                    if (63 > $y) {
                        if (mt_rand(0, 30) === 0) {
                            $world->setBlock(new Vector3($x, $y, $z), BlockFactory::getInstance()->get(BlockLegacyIds::EMERALD_ORE, 0));
                        } else {
                            $world->setBlock(new Vector3($x, $y, $z), BlockFactory::getInstance()->get(BlockLegacyIds::STONE, 0));
                        }
                    } else if ($y === 63) {
                        $world->setBlock(new Vector3($x, $y, $z), BlockFactory::getInstance()->get($bid, $meta));
                    } else {
                        $world->setBlock(new Vector3($x, $y, $z), BlockFactory::getInstance()->get(BlockLegacyIds::AIR, 0));
                    }
                }
            }
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new IntegerArgument("id", true));
        $this->registerArgument(0, new OptionArgument("id", ["all"], true));
    }
}