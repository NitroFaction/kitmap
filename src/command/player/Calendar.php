<?php

namespace Kitmap\command\player;

use CortexPE\Commando\BaseCommand;
use DateTime;
use DateTimeZone;
use Kitmap\enchantment\EnchantmentIds;
use Kitmap\handler\Cache;
use Kitmap\handler\Pack as PackHandler;
use Kitmap\Session;
use Kitmap\Util;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\command\CommandSender;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\DoorCrashSound;
use pocketmine\world\sound\XpLevelUpSound;

class Calendar extends BaseCommand
{

    private static array $rewards = [];

    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "calendar",
            "Consulter le calendrier de l'avent"
        );

        $this->setAliases(["calendrier"]);
        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);

        $this->loadRewards();
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            self::showCalendar($sender);
        }
    }

    private function loadRewards(): void
    {
        $rewardsData = Cache::$config["calendar"];

        foreach ($rewardsData as $day => $rewards) {
            foreach ($rewards as $data) {
                $rewardData = explode(":", $data);
                $type = $rewardData[0];
                $count = end($rewardData);

                if ($type === "item") {
                    $itemName = $rewardData[1];
                    $reward = Util::getItemByName($itemName)->setCount($count);
                } else {
                    $itemData = match ($type) {
                        "money" => ["§r§qBillet de " . Util::formatNumberWithSuffix($count), 0],
                        "pack" => ["§r§q" . $count . " Pack(s)", 2],
                        "gem" => ["§r§q" . $count . " Gemme(s)", 3],
                    };

                    $reward = PackHandler::initializeItem(VanillaItems::PAPER(), [$itemData[0], $itemData[1], $count]);
                }

                self::$rewards[$day][] = $reward;
            }
        }
    }

    private static function showCalendar(Player $player): void
    {
        /* @noinspection PhpUnhandledExceptionInspection */
        $day = (new DateTime('now', new DateTimeZone('Europe/Paris')))->format('j');

        if ($day >= 1 && $day <= 24) {
            $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
            $menu->setName("Calendrier de l'avent");

            self::buildCalendar($player, $day, $menu);

            $menu->setListener(InvMenu::readonly(function (DeterministicInvMenuTransaction $invMenuTransaction) use ($day): void {
                $player = $invMenuTransaction->getPlayer();
                $item = $invMenuTransaction->getItemClicked();

                $session = Session::get($player);

                if (!is_null($item->getNamedTag()->getTag("Day"))) {
                    if (
                        $item->hasEnchantment(EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::GLOW)) &&
                        $item->equals(VanillaBlocks::CONCRETE()->setColor(DyeColor::BLACK)->asItem(), false, false)
                    ) {
                        Util::removeCurrentWindow($player);

                        $rewards = self::$rewards[$day];
                        Util::addItems($player, $rewards);

                        $session->data["calendar"][$day] = true;

                        $rewardsName = array_map(fn (Item $item) => TextFormat::clean($item->getName()), $rewards);
                        $formattedRewards = implode(", ", $rewardsName);

                        $player->sendMessage(Util::PREFIX . "§fEn ce §q" . $day . " Décembre§f, voici la liste des lots que vous avez récupéré : §q" . $formattedRewards . " §f!");
                        $player->sendTitle("§l§q» §r§a" . $day . " Décembre §l§q«§r", "§7Vos récompenses ont été mis dans votre inventaire !");
                        $player->broadcastSound(new XpLevelUpSound(5));

                        Server::getInstance()->broadcastTip(Util::PREFIX . "Le joueur §q" . $player->getName() . " §fvient de récupérer ses récompenses du §q" . $day . " Décembre ! §8(§7/calendar§8)");
                    } else {
                        $player->broadcastSound(new DoorCrashSound(), [$player]);
                    }
                } else {
                    $player->broadcastSound(new DoorCrashSound(), [$player]);
                }
            }));

            $menu->send($player);
        }
    }

    private static function buildCalendar(Player $player, int $actualDay, InvMenu $menu): void
    {
        $patern = [
            '1:1' => 49,
            '2:4' => 39,
            '5:11' => 28,
            '12:20' => 18,
            '21:23' => 12,
            '24:24' => 4
        ];

        $calendarData = Session::get($player)->data["calendar"];

        foreach ($calendarData as $day => $claimed) {
            foreach ($patern as $days => $baseIndex) {
                [$minDay, $maxDay] = explode(":", $days);

                if ($day >= $minDay && $day <= $maxDay) {
                    $additionalIndex = $day - $minDay;
                    $index = $baseIndex + $additionalIndex;
                }
            }

            if ($claimed) {
                $item = VanillaBlocks::CONCRETE()->setColor(DyeColor::LIME)->asItem();
            } else {
                $color = $day >= $actualDay ? DyeColor::BLACK : DyeColor::RED;
                $item = VanillaBlocks::CONCRETE()->setColor($color)->asItem();
            }

            $isActualDay = $day === $actualDay;

            if ($isActualDay) {
                $item->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::GLOW)));
            }

            $formattedState = match (true) {
                $claimed => "§aRéclamé",
                !$claimed && $isActualDay => "§eRéclamable",
                !$claimed && $day < $actualDay => "§cNon réclamé",
                default => "§cNon réclamable"
            };

            $customName = "§l§q» §r§a" . $day . " Décembre §l§q«\n§r§l§q| §r§fÉtat§8: " . $formattedState;

            $item->setCustomName($customName);
            $item->getNamedTag()->setInt("Day", $day);

            $menu->getInventory()->setItem($index ?? 0, $item);
        }
    }

    protected function prepare(): void
    {
    }
}