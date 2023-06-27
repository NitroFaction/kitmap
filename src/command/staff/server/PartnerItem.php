<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\server;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\OtherAPI;
use Kitmap\handler\PartnerItemsAPI;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class PartnerItem extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "partneritem",
            "Permet de se donner un partner item de son choix"
        );

        $this->setAliases(["pp"]);
        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $amount = $args["montant"] ?? 1;

            $item = PartnerItemsAPI::createItem($args["item"]);
            $item->setCount($amount);

            OtherAPI::addItem($sender, $item);
            $sender->sendMessage(Util::PREFIX . "Vous venez de recevoir " . $amount . " §e" . $args["item"]);
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("item", array_keys(Cache::$config["partneritems"])));
        $this->registerArgument(1, new IntegerArgument("montant", true));
    }
}