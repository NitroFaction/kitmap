<?php /** @noinspection PhpUnused */

namespace NCore\command\player\util\faction;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use muqsit\invmenu\type\InvMenuTypeIds;
use NCore\Base;
use NCore\handler\Cache;
use NCore\handler\OtherAPI;
use NCore\Session;
use NCore\task\TeleportationTask;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\item\ItemFactory;
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
                $item = ItemFactory::getInstance()->get($data["id"]);
                $item->setCustomName("§r§e§l" . strtoupper($name) . "\n\n" . $data["description"] . "\n\n§o§eCliquez sur l'item pour y être téléporté");

                $inventory->setItem($data["slot"], $item);
            }

            $menu->send($sender);
        }
    }

    private function tpToEvent(Player $player, string $event): void
    {
        $data = Cache::$config["events"][strtolower($event)];
        $time = OtherAPI::getTpTime($player);

        $xyz = $data["xyz"];
        list ($x, $y, $z) = explode(":", $xyz[array_rand($xyz)]);

        $vector = new Position(intval($x), intval($y), intval($z), Base::getInstance()->getServer()->getWorldManager()->getDefaultWorld());

        $player->sendMessage(Util::PREFIX . "Vous allez être teleporté dans §e" . max($time, 0) . " §fseconde(s), veuillez ne pas bouger");
        $player->getEffects()->add(new EffectInstance(VanillaEffects::BLINDNESS(), 20 * ($time + 1), 1, false));

        Base::getInstance()->getScheduler()->scheduleRepeatingTask(new TeleportationTask($player, $vector), 20);
        Session::get($player)->setCooldown("teleportation", $time, [OtherAPI::getPlace($player)]);
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("event", array_keys(Cache::$config["events"]), true));
    }
}