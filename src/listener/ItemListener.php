<?php /** @noinspection PhpUnused */

namespace Kitmap\listener;

use Kitmap\command\player\rank\Enderchest;
use Kitmap\handler\PackAPI;
use Kitmap\handler\PartnerItemsAPI;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\block\inventory\EnderChestInventory;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\EnderPearl;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use Util\util\IdsUtils;

class ItemListener implements Listener
{
    public function onBow(EntityShootBowEvent $event): void
    {
        $event->cancel();
    }

    /**
     * @handleCancelled
     */
    public function onUse(PlayerItemUseEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $event->getItem();

        $session = Session::get($player);

        if ($session->data["staff_mod"][0]) {
            switch ($item->getCustomName()) {
                case "§r" . Util::PREFIX . "Vanish §e§l«":
                    $player->chat("/vanish");
                    break;
                case "§r" . Util::PREFIX . "Random Tp §e§l«":
                    $player->chat("/randomtp");
                    break;
                case "§r" . Util::PREFIX . "Spectateur §e§l«":
                    $player->chat("/spec");
                    break;
            }
        }

        if (!$event->isCancelled()) {
            $executePp = PartnerItemsAPI::executeInteractPartnerItem($player, $event);
            $executePack = PackAPI::executeInteractPackItem($player, $event);

            if ($executePack || $executePp) {
                return;
            } elseif ($item->getId() === ItemIds::SNOWBALL) {
                $event->cancel();
            }

            if ($item instanceof EnderPearl) {
                if ($session->inCooldown("enderpearl")) {
                    $player->sendMessage(Util::PREFIX . "Veuillez attendre §e" . ($session->getCooldownData("enderpearl")[0] - time()) . " §fsecondes avant de relancer une nouvelle perle");
                    $event->cancel();
                } else {
                    if ($session->inCooldown("_antipearl")) {
                        $player->sendTip(Util::PREFIX . "Veuillez attendre §e" . ($session->getCooldownData("_antipearl")[0] - time()) . " §fsecondes avant de relancer une nouvelle perle");
                        $event->cancel();
                        return;
                    } elseif (!is_null($item->getNamedTag()->getTag("partneritem"))) {
                        $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas utiliser cette perle");
                        $event->cancel();
                        return;
                    } elseif (Util::isPlayerAimOnAntiBack($player)) {
                        $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas perle en visant un bloc antiback");
                        $event->cancel();
                        return;
                    }

                    $session->setCooldown("enderpearl", 15, [$player->getPosition()]);
                }
            }
        }
    }

    /**
     * @handleCancelled
     */
    public function onConsume(PlayerItemConsumeEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $event->getItem();

        $session = Session::get($player);

        switch ($item->getId()) {
            case IdsUtils::COMBINED_COOKIE:
                if ($session->inCooldown("cookie_combined")) {
                    $player->sendMessage(Util::PREFIX . "Veuillez attendre §e" . ($session->getCooldownData("cookie_combined")[0] - time()) . " §fsecondes avant de remanger un cookie combiné");
                    $event->cancel();
                } else {
                    $player->getEffects()->add(new EffectInstance(VanillaEffects::ABSORPTION(), (10 * 20), 0, false));
                    $player->getEffects()->add(new EffectInstance(VanillaEffects::REGENERATION(), (10 * 20), 0, false));
                    $player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), (240 * 20), 0, false));
                    $player->getEffects()->add(new EffectInstance(VanillaEffects::STRENGTH(), (240 * 20), 0, false));

                    $session->setCooldown("cookie_combined", 25);
                }
                return;
            case IdsUtils::REGENERATION_COOKIE:
                if ($session->inCooldown("cookie_regeneration")) {
                    $player->sendMessage(Util::PREFIX . "Veuillez attendre §e" . ($session->getCooldownData("cookie_regeneration")[0] - time()) . " §fsecondes avant de remanger un cookie de regeneration");
                    $event->cancel();
                } else {
                    $player->getEffects()->add(new EffectInstance(VanillaEffects::REGENERATION(), (10 * 20), 0, false));
                    $session->setCooldown("cookie_regeneration", 25);
                }
                return;
            case IdsUtils::SPEED_COOKIE:
                if ($session->inCooldown("cookie_speed")) {
                    $player->sendMessage(Util::PREFIX . "Veuillez attendre §e" . ($session->getCooldownData("cookie_speed")[0] - time()) . " §fsecondes avant de remanger un cookie de vitesse");
                    $event->cancel();
                } else {
                    $player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), (240 * 20), 0, false));
                    $session->setCooldown("cookie_speed", 25);
                }
                return;
            case IdsUtils::STRENGTH_COOKIE:
                if ($session->inCooldown("cookie_strength")) {
                    $player->sendMessage(Util::PREFIX . "Veuillez attendre §e" . ($session->getCooldownData("cookie_strength")[0] - time()) . " §fsecondes avant de remanger un cookie de force");
                    $event->cancel();
                } else {
                    $player->getEffects()->add(new EffectInstance(VanillaEffects::STRENGTH(), (240 * 20), 0, false));
                    $session->setCooldown("cookie_strength", 25);
                }
                return;
            case ItemIds::GOLDEN_APPLE:
                $event->cancel();
                return;
        }
    }

    public function onDrop(PlayerDropItemEvent $event): void
    {
        if (Session::get($event->getPlayer())->data["staff_mod"][0]) {
            $event->cancel();
        }
    }

    public function onPick(EntityItemPickupEvent $event): void
    {
        $entity = $event->getEntity();

        if ($entity instanceof Player) {
            if (Session::get($entity)->data["staff_mod"][0]) {
                $event->cancel();
            }
        }
    }

    public function onTransaction(InventoryTransactionEvent $event): void
    {
        $transaction = $event->getTransaction();
        $player = $transaction->getSource();

        $staff = Session::get($player)->data["staff_mod"][0];

        foreach ($transaction->getActions() as $action) {
            $sourceItem = $action->getSourceItem();
            $targetItem = $action->getTargetItem();

            if ($targetItem->getCount() > $targetItem->getMaxStackSize() || $sourceItem->getCount() > $sourceItem->getMaxStackSize()) {
                $event->cancel();
                return;
            }

            if (!is_null($targetItem->getNamedTag()->getTag("menu_item")) || !is_null($sourceItem->getNamedTag()->getTag("menu_item"))) {
                $player->getInventory()->clearAll();
                Main::getInstance()->getLogger()->warning("Dupli: " . $player->getName() . " son inventaire a été clear");
            }

            if ($action instanceof SlotChangeAction && ($staff || $player->isImmobile())) {
                $event->cancel();
                return;
            }

            $nbt = ($sourceItem->getNamedTag() ?? new CompoundTag());
            $_nbt = ($targetItem->getNamedTag() ?? new CompoundTag());

            foreach ($transaction->getInventories() as $inventory) {
                if ($inventory instanceof EnderChestInventory) {
                    if (($nbt->getTag("enderchest_slots") && $nbt->getString("enderchest_slots") === "restricted") || ($_nbt->getTag("enderchest_slots") && $_nbt->getString("enderchest_slots") === "restricted")) {
                        $event->cancel();
                        return;
                    }
                }
            }
        }
    }

    public function onOpenInventory(InventoryOpenEvent $event): void
    {
        $player = $event->getPlayer();
        $inventory = $event->getInventory();

        if ($inventory instanceof EnderChestInventory) {
            Enderchest::setEnderchestGlass($player, $inventory);
        }
    }
}