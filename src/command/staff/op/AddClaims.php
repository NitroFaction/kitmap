<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use pocketmine\command\CommandSender;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\VanillaItems;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\world\format\Chunk;

class AddClaims extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "addclaims",
            "Permet d'ajouter des claims"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public static function addClaim(float|int $x, float|int $z): bool
    {
        $chunkX = $x >> Chunk::COORD_BIT_SIZE;
        $chunkZ = $z >> Chunk::COORD_BIT_SIZE;

        $chunk = $chunkX . ":" . $chunkZ;

        if (!in_array($chunk, Cache::$data["claims"])) {
            Cache::$data["claims"][] = $chunk;
            return true;
        }
        return false;
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $item = VanillaItems::STONE_AXE();

            $item->setCustomName("§r§9Claims Axe");

            $item->getNamedTag()->setInt("claims", 1);
            $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 10));

            $sender->getInventory()->setItemInHand($item);
        }
    }

    protected function prepare(): void
    {
    }
}