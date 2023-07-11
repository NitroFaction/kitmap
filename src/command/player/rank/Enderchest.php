<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player\rank;

use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Rank;
use Kitmap\Session;
use Kitmap\Util;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\command\CommandSender;
use pocketmine\inventory\Inventory;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\world\sound\EnderChestOpenSound;

class Enderchest extends BaseCommand {
	public function __construct(PluginBase $plugin) {
		parent::__construct(
			$plugin,
			"enderchest",
			"Ouvre un enderchest n'importe où"
		);

		$this->setAliases([ "ec" ]);
		$this->setPermissions([ DefaultPermissions::ROOT_USER ]);
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void {
		if ($sender instanceof Player) {
			$session = Session::get($sender);

			if ($session->data["staff_mod"][0] || $sender->getGamemode() === GameMode::SPECTATOR()) {
				$sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas accèder à votre enderchest en étant en staff mod");
				return;
			} elseif (!Rank::hasRank($sender, "champion")) {
				$sender->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de faire cela");
				return;
			} elseif ($session->inCooldown("combat")) {
				$sender->sendMessage(Util::PREFIX . "Cette commande est interdite en combat");
				return;
			}

			$menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);
			$menu->setName("Enderchest");

			$menu->setListener(function(InvMenuTransaction $transaction) use ($sender) : InvMenuTransactionResult {
				$nbt = ($transaction->getOut()->getNamedTag() ?? new CompoundTag());

				if (($nbt->getTag("enderchest_slots") && $nbt->getString("enderchest_slots") === "restricted")) {
					return $transaction->discard();
				}

				$sender->getEnderInventory()->setItem($transaction->getAction()->getSlot(), $transaction->getIn());
				return $transaction->continue();
			});

			self::setEnderchestGlass($sender, $menu->getInventory());

			$menu->getInventory()->setContents($sender->getEnderInventory()->getContents());
			$menu->send($sender);

			$sender->broadcastSound(new EnderChestOpenSound());
		}
	}

	public static function setEnderchestGlass(Player $player, Inventory $inventory) : void {
		$rank = Rank::getEqualRank($player->getName());
		$slots = Rank::getRankValue($rank, "enderchest");

		$enderchest = $player->getEnderInventory();

		for ($i = 1; $i <= 26; $i++) {
			$item = $player->getEnderInventory()->getItem($i);
			$nbt = ($item->getNamedTag() ?? new CompoundTag());

			if ($nbt->getTag("enderchest_slots") && $nbt->getString("enderchest_slots") === "restricted") {
				$inventory->setItem($i, VanillaItems::AIR());
			}

			if ($slots <= $i) {
				$glass = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::RED())->asItem();
				$glass->setCustomName(" ");

				$nbt = ($glass->getNamedTag() ?? new CompoundTag());
				$nbt->setString("enderchest_slots", "restricted");
				$glass->setNamedTag($nbt);

				$enderchest->setItem($i, $glass);
				$slots++;
			}
		}
	}

	protected function prepare() : void {
	}
}