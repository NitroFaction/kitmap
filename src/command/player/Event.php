<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use muqsit\invmenu\type\InvMenuTypeIds;
use Kitmap\handler\Cache;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\task\TeleportationTask;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\world\Position;

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
            } elseif ($session->inCooldown("teleportation")) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas executer cette commande en teleportation");
                return;
            } elseif (isset($args["event"])) {
                $this->tpToEvent($sender, $args["event"]);
                return;
            }

            $menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);
            $inventory = $menu->getInventory();

            $menu->setName("Events");

            $menu->setListener(InvMenu::readonly(function (DeterministicInvMenuTransaction $transaction): void {
                $player = $transaction->getPlayer();
                $slot = $transaction->getAction()->getSlot();

                $slots = array_column(Cache::$config["events"], "slot");

                $event = array_search($slot, $slots);
                $event = array_keys(Cache::$config["events"])[$event];

                $this->tpToEvent($player, $event);
            }));

            foreach (Cache::$config["events"] as $name => $data) {
                $item = VanillaItems::getInstance()->get($data["id"]);
                $item->setCustomName("§r§e§l" . strtoupper($name) . "\n\n" . $data["description"] . "\n\n§o§eCliquez sur l'item pour y être téléporté");

                $inventory->setItem($data["slot"], $item);
            }

            $menu->send($sender);
        }
    }

    private function tpToEvent(Player $player, string $event): void
    {
        $data = Cache::$config["events"][strtolower($event)];
        list ($x, $y, $z) = explode(":", $data["xyz"][array_rand($data["xyz"])]);

        $position = new Position(intval($x), intval($y), intval($z), Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld());
        Main::getInstance()->getScheduler()->scheduleRepeatingTask(new TeleportationTask($player, $position), 20);
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("event", array_keys(Cache::$config["events"]), true));
    }
}