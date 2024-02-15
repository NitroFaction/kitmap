<?php

namespace Kitmap\command\faction\subcommands\admin\claim;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\BaseSubCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\Faction;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Reset extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "reset",
            "Reset le claim à la position ou la commande est executé"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $claim = Faction::inClaim($sender->getPosition()->getFloorX(), $sender->getPosition()->getFloorZ());

            if (!$claim[0]) {
                $sender->sendMessage(Util::PREFIX . "Vous ne vous trouvez pas dans un claim");
                return;
            }

            $world = Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld();
            $chunk = clone $world->loadChunk(intval($args["chunkX"]), intval($args["chunkZ"]));

            [$chunkX, $chunkZ] = explode(":", $claim[2]);
            $world->setChunk(intval($chunkX), intval($chunkZ), $chunk);

            Cache::$factions[$claim[1]]["claim"] = null;
            unset(Cache::$claims[$claim[2]]);

            $sender->sendMessage(Util::PREFIX . "Vous venez de reset le claim de la §9" . $claim[1]);
        }
    }

    protected function prepare(): void
    {
        $this->registerSubCommand(new All());
        $this->registerArgument(0, new IntegerArgument("chunkX"));
        $this->registerArgument(1, new IntegerArgument("chunkZ"));
    }
}