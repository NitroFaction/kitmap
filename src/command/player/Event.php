<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\task\TeleportationTask;
use Kitmap\Util;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\command\CommandSender;
use pocketmine\entity\Location;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Event extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "event",
            "Acceder au menu des events"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->inCooldown("combat")) {
                $sender->sendMessage(Util::PREFIX . "Cette commande est interdite en combat");
                return;
            } else if ($session->inCooldown("teleportation")) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas executer cette commande en teleportation");
                return;
            } else if (isset($args["event"])) {
                $this->tpToEvent($sender, $args["event"]);
                return;
            }

            $menu = InvMenu::create(InvMenuTypeIds::TYPE_HOPPER);
            $inventory = $menu->getInventory();

            $eventsData = Cache::$config["events"];

            $menu->setName("Events");

            $menu->setListener(InvMenu::readonly(function (DeterministicInvMenuTransaction $transaction) use ($eventsData): void {
                $player = $transaction->getPlayer();
                $slot = $transaction->getAction()->getSlot();

                $slots = array_column($eventsData, "slot");

                $event = array_search($slot, $slots);
                $event = array_keys($eventsData)[$event];

                $this->tpToEvent($player, $event);
                Util::removeCurrentWindow($player);
            }));

            foreach ($eventsData as $name => $data) {
                $item = Util::getItemByName($data["item"]);
                $item->setCustomName("§r§9§l" . strtoupper($name) . "\n\n" . $data["description"] . "\n\n§o§9Cliquez sur l'item pour y être téléporté");

                $inventory->setItem($data["slot"], $item);
            }

            $menu->send($sender);
        }
    }

    private function tpToEvent(Player $player, string $event): void
    {
        $data = Cache::$config["events"][strtolower($event)];
        [$x, $y, $z] = explode(":", $data["positions"][array_rand($data["positions"])]);

        $position = new Location(floatval($x), intval($y), floatval($z), Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld(), 0, 0);
        Main::getInstance()->getScheduler()->scheduleRepeatingTask(new TeleportationTask($player, $position), 20);
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("event", array_keys(Cache::$config["events"]), true));
    }
}
