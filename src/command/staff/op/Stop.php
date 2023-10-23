<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\Main;
use Kitmap\task\StopTask;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task;

class Stop extends BaseCommand
{
    public static ?Task $task = null;

    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "stop",
            "Arrêter le serveur"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $seconds = intval($args["secondes"] ?? -1);

        if ($seconds <= 0) {
            Main::getInstance()->getServer()->shutdown();
            return;
        }

        Main::getInstance()->getScheduler()->scheduleRepeatingTask(new StopTask($seconds), 20);
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new IntegerArgument("secondes", true));
    }
}