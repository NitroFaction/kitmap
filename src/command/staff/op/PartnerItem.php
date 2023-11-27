<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\BaseCommand;
use Element\util\args\OptionArgument;
use Kitmap\handler\Cache;
use Kitmap\handler\PartnerItems;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
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
        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $amount = $args["montant"] ?? 1;

            $item = PartnerItems::createItem($args["item"]);
            $item->setCount($amount);

            Util::addItem($sender, $item);
            $sender->sendMessage(Util::PREFIX . "Vous venez de recevoir " . $amount . " §q" . $args["item"]);
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("item", array_keys(Cache::$config["partneritems"])));
        $this->registerArgument(1, new IntegerArgument("montant", true));
    }
}