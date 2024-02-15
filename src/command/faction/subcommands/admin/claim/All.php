<?php

namespace Kitmap\command\faction\subcommands\admin\claim;

use CortexPE\Commando\args\BooleanArgument;
use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\BaseSubCommand;
use Kitmap\handler\Cache;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class All extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "all",
            "Reset tout les chunks"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player && $sender->getName() === "MaXoooZ") {
            $newChunkX = intval($args["chunkX"]);
            $newChunkZ = intval($args["chunkZ"]);

            $world = Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld();

            $chunks = [
                clone $world->loadChunk($newChunkX, $newChunkZ),
                clone $world->loadChunk($newChunkX, ($args["change"] ?? false) ? $newChunkZ + 1 : $newChunkZ)
            ];

            $i = 0;

            foreach (Cache::$data["claims"] as $claim) {
                $i++;

                [$chunkX, $chunkZ] = explode(":", $claim);
                $world->setChunk(intval($chunkX), intval($chunkZ), $chunks[$i % 2]);
            }

            Cache::$claims = [];

            foreach (Cache::$factions as $faction => $ignore) {
                Cache::$factions[$faction]["claim"] = null;
            }

            $sender->sendMessage(Util::PREFIX . "Tous les claims ont été reset");
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new IntegerArgument("chunkX"));
        $this->registerArgument(1, new IntegerArgument("chunkZ"));
        $this->registerArgument(2, new BooleanArgument("change", true));
    }
}