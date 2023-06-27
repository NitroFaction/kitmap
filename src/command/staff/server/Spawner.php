<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\server;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\OtherAPI;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class Spawner extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "spawner",
            "Permet de se donner un spawner de son choix"
        );

        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $amount = $args["montant"] ?? 1;

            $item = self::createItem($args["item"]);
            $item->setCount($amount);

            OtherAPI::addItem($sender, $item);
            $sender->sendMessage(Util::PREFIX . "Vous venez de recevoir " . $amount . " §e" . TextFormat::clean($item->getCustomName()));
        }
    }

    public static function createItem(string $name): Item
    {
        $customName = match ($name) {
            "emerald_golem" => "§r§fSpawner à Golem d'Émeraude",
            "villager_farmer" => "§r§fSpawner de Villageois Farmeur",
            "goblin" => "§r§fSpawner à Goblin",
            default => null,
        };

        $item = ItemFactory::getInstance()->get(ItemIds::MONSTER_SPAWNER);
        $item->setCustomName($customName);

        return $item;
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("item", ["emerald_golem", "goblin", "villager_farmer"]));
        $this->registerArgument(1, new IntegerArgument("montant", true));
    }
}