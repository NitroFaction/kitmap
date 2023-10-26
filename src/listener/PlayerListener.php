<?php /** @noinspection PhpUnused */

namespace Kitmap\listener;

use Kitmap\command\player\{Anvil, Enchant, rank\Enderchest};
use Kitmap\command\staff\{Ban, LastInventory, Question, Vanish};
use Kitmap\command\util\Bienvenue;
use Kitmap\entity\{AntiBackBallEntity, LightningBolt, LogoutEntity, SwitcherEntity};
use Kitmap\handler\{Cache, Faction, Pack, PartnerItems, Rank, Sanction};
use Kitmap\enchantment\EnchantmentIds;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\task\repeat\PlayerTask;
use Kitmap\Util;
use MaXoooZ\Util\item\ExtraVanillaItems;
use pocketmine\block\{Barrel,
    CartographyTable,
    Chest,
    CocoaBlock,
    CraftingTable,
    Crops,
    Door,
    FenceGate,
    Fire,
    Furnace,
    GlowLichen,
    Hopper,
    inventory\EnderChestInventory,
    Lava,
    Liquid,
    SweetBerryBush,
    Trapdoor,
    utils\DyeColor,
    VanillaBlocks,
    Wheat
};
use pocketmine\entity\animation\HurtAnimation;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\entity\effect\{EffectInstance, VanillaEffects};
use pocketmine\event\block\{BlockBreakEvent, BlockGrowEvent, BlockPlaceEvent, BlockSpreadEvent, BlockUpdateEvent};
use pocketmine\event\entity\{EntityDamageByEntityEvent,
    EntityDamageEvent,
    EntityItemPickupEvent,
    EntityShootBowEvent,
    EntityTeleportEvent,
    EntityTrampleFarmlandEvent,
    ItemSpawnEvent,
    ProjectileHitEntityEvent
};
use pocketmine\event\inventory\{CraftItemEvent, InventoryOpenEvent, InventoryTransactionEvent};
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\event\Listener;
use pocketmine\event\player\{PlayerBucketEvent,
    PlayerChatEvent,
    PlayerDataSaveEvent,
    PlayerDeathEvent,
    PlayerInteractEvent,
    PlayerItemConsumeEvent,
    PlayerItemUseEvent,
    PlayerJoinEvent,
    PlayerMissSwingEvent,
    PlayerPreLoginEvent,
    PlayerQuitEvent,
    PlayerRespawnEvent};
use pocketmine\event\server\CommandEvent;
use pocketmine\event\server\DataPacketDecodeEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\CallbackInventoryListener;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\{Axe,
    Bucket,
    Durable,
    EnderPearl,
    Hoe,
    Item,
    PaintingItem,
    PotionType,
    Shovel,
    Stick,
    VanillaItems
};
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryContentPacket;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\SetTimePacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\{GameMode, Player};
use pocketmine\player\chat\LegacyRawChatFormatter;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\sound\AmethystBlockChimeSound;
use pocketmine\world\sound\BlockBreakSound;
use pocketmine\world\sound\EndermanTeleportSound;
use Symfony\Component\Filesystem\Path;

class PlayerListener implements Listener
{
    public function onInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();

        $block = $event->getBlock();
        $item = $event->getItem();

        if ($item->equals(VanillaItems::FLINT_AND_STEEL())) {
            $event->cancel();
            return;
        }

        if (
            $event->getAction() === $event::RIGHT_CLICK_BLOCK &&
            (($block instanceof Door || $block instanceof Trapdoor || $block instanceof FenceGate || $block instanceof Furnace || $block instanceof SweetBerryBush || $block instanceof GlowLichen || $block instanceof CraftingTable || $block instanceof CartographyTable || $block instanceof Chest || $block instanceof Barrel || $block instanceof Hopper) || ($item instanceof Bucket || $item instanceof Hoe || $item instanceof Axe || $item instanceof Shovel || $item instanceof PaintingItem || $item instanceof Stick)) &&
            !Faction::canBuild($player, $block, "interact")
        ) {
            $event->cancel();

            if ($block instanceof Door || $block instanceof Trapdoor || $block instanceof FenceGate) {
                Util::antiBlockGlitch($player);
            }
        } else if (!$player->isSneaking() && $event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK && $block->hasSameTypeId(VanillaBlocks::ANVIL())) {
            $event->cancel();
            $player->removeCurrentWindow();

            Anvil::openAnvil($player);
        } else if (!$player->isSneaking() && $event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK && $block->hasSameTypeId(VanillaBlocks::ENCHANTING_TABLE())) {
            $event->cancel();
            $player->removeCurrentWindow();

            Enchant::openEnchantTable($player, false);
        }

        [$x,$y,$z] = explode(":", Cache::$config["pack"]);

        if ($block->getPosition()->getX() === intval($x) && $block->getPosition()->getY() === intval($y) && $block->getPosition()->getZ() === intval($z)) {
            $event->cancel();
            $player->removeCurrentWindow();

            Pack::openPackUI($player);
        }
    }

    public function onChat(PlayerChatEvent $event): void
    {
        $player = $event->getPlayer();
        $message = TextFormat::clean($event->getMessage());

        $session = Session::get($player);

        if (str_contains($message, "@here") && !$player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            $event->cancel();
            $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas utiliser §6@here §fdans votre message");
            return;
        }

        if (Question::$currentEvent !== 0) {
            $valid = false;

            switch (Question::$currentEvent) {
                case 1:
                    if ($event->getMessage() === Question::$currentReply) {
                        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§6" . $player->getDisplayName() . " §fa gagné §65k$ §fen ayant réécrit le code §6" . Question::$currentReply . " §fen premier !");
                        $valid = true;
                    }
                    break;
                case 2:
                    if (strtolower($event->getMessage()) === Question::$currentReply) {
                        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§6" . $player->getDisplayName() . " §fa gagné §65k$ §fen ayant trouver le mot §6" . Question::$currentReply . " §fen premier !");
                        $valid = true;
                    }
                    break;
                case 3:
                    if ($event->getMessage() === strval(Question::$currentReply)) {
                        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§6" . $player->getDisplayName() . " §fa gagné §65k$ §fen ayant répondu au calcul §6" . Question::$currentReply . " §fen premier !");
                        $valid = true;
                    }
                    break;
            }

            if ($valid) {
                $event->cancel();
                $session->addValue("money", 5000);

                Question::$currentEvent = 0;
                Question::$currentReply = null;
            }
        }

        if ($session->inCooldown("chat")) {
            $event->cancel();
        } else {
            if (!$player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
                $session->setCooldown("chat", 2);
            }
        }

        if (($session->data["faction_chat"] || $event->getMessage()[0] === "-") && Faction::hasFaction($player)) {
            if (!$session->data["faction_chat"]) {
                $message = substr($message, 1);
            }

            $faction = $session->data["faction"];
            $event->cancel();

            Main::getInstance()->getLogger()->info("[F] [" . $faction . "] " . $player->getName() . " » " . $message);
            Faction::broadcastMessage($faction, "§6[§fF§6] §f" . $player->getName() . " " . Util::PREFIX . $message);

            return;
        } else if ($session->inCooldown("mute")) {
            $format = Util::formatDurationFromSeconds($session->getCooldownData("mute")[0] - time());
            $player->sendMessage(Util::PREFIX . "Vous êtes mute, temps restant: §6" . $format);

            $event->cancel();
            return;
        }

        $rank = ($player->getName() === $player->getDisplayName()) ? Rank::getRank($player->getName()) : "joueur";
        $message = Rank::setReplace(Rank::getRankValue($rank, "chat"), $player, $message);

        $event->setFormatter(new LegacyRawChatFormatter($message));
    }

    public function onJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
        $session = Session::get($player);

        $event->setJoinMessage("");
        $player->setViewDistance(8);

        if (Ban::checkBan($event)) {
            return;
        }

        Main::getInstance()->getServer()->broadcastTip("§a+ " . $player->getName() . " +");

        if (Faction::hasFaction($player)) {
            Cache::$factions[$session->data["faction"]]["activity"][date("m-d")] = $player->getName();
            Faction::broadcastMessage($session->data["faction"], "§6[§fF§6] §fLe joueur de votre faction §6" . $player->getName() . " §fvient de se connecter");
        }

        foreach (Vanish::$vanish as $target) {
            $target = Main::getInstance()->getServer()->getPlayerExact($target);

            if ($target instanceof Player) {
                if ($target->hasPermission(Rank::GROUP_STAFF) || $target->getName() === $player->getName()) {
                    continue;
                }
                $target->hidePlayer($player);
            }
        }

        if (!$player->hasPlayedBefore()) {
            $path = Path::join(Main::getInstance()->getServer()->getDataPath(), "players");
            $count = count(glob($path . "/*")) + 1;

            Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§6" . $player->getName() . " §fa rejoint le serveur pour la §6première §ffois ! Souhaitez lui la §6bienvenue §favec la commande §6/bvn §f(#§6" . $count . "§f)!");

            Bienvenue::$alreadyWished = [];
            Bienvenue::$lastJoin = $player->getName();
        }

        $player->getArmorInventory()->getListeners()->add(new CallbackInventoryListener(function (Inventory $inventory, int $slot, Item $oldItem): void {
            if ($inventory instanceof ArmorInventory) {
                $targetItem = $inventory->getItem($slot);

                if ($targetItem->getTypeId() === VanillaItems::TURTLE_HELMET()->getTypeId()) {
                    $inventory->getHolder()->getEffects()->add(new EffectInstance(VanillaEffects::FIRE_RESISTANCE(), 20 * 60 * 60 * 24, 0, false));
                    $inventory->getHolder()->getEffects()->add(new EffectInstance(VanillaEffects::HASTE(), 20 * 60 * 60 * 24, 1, false));
                    $inventory->getHolder()->getEffects()->add(new EffectInstance(VanillaEffects::JUMP_BOOST(), 20 * 60 * 60 * 24, 2, false));
                } else if ($oldItem->getTypeId() === VanillaItems::TURTLE_HELMET()->getTypeId()) {
                    $inventory->getHolder()->getEffects()->remove(VanillaEffects::FIRE_RESISTANCE());
                    $inventory->getHolder()->getEffects()->remove(VanillaEffects::HASTE());
                    $inventory->getHolder()->getEffects()->remove(VanillaEffects::JUMP_BOOST());
                }
            }
        }, null));

        Util::givePlayerPreferences($player);

        Rank::updateNameTag($player);
        Rank::addPermissions($player);
    }

    public function onEntityTeleport(EntityTeleportEvent $event): void
    {
        $entity = $event->getEntity();

        $from = $event->getFrom();
        $to = $event->getTo();

        if (!$entity instanceof Player) {
            return;
        }

        if (
            str_starts_with($from->getWorld()->getFolderName(), "box-") &&
            !str_starts_with($to->getWorld()->getFolderName(), "box-")
        ) {
            if (!Session::get($entity)->data["staff_mod"][0] && !$entity->isCreative()) {
                $entity->setFlying(false);
                $entity->setAllowFlight(false);
            }
        }
    }

    public function onRespawn(PlayerRespawnEvent $event): void
    {
        Util::givePlayerPreferences($event->getPlayer());
    }

    public function onQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();

        Main::getInstance()->getServer()->broadcastTip("§c- " . $player->getName() . " -");
        $event->setQuitMessage("");

        if (Util::getTpTime($player) > 0) {
            $entity = new LogoutEntity($player->getLocation(), $player->getSkin());
            $entity->initEntityB($player);
            $entity->spawnToAll();
        }

        Session::get($player)->saveSessionData();
    }

    public function onDeath(PlayerDeathEvent $event): void
    {
        $player = $event->getPlayer();
        $session = Session::get($player);

        $event->setDeathMessage("");

        $session->removeCooldown("combat");
        $session->addValue("death", 1);

        $playerBounty = $session->data["bounty"];

        if ($playerBounty > 0) {
            $session->addValue("bounty", $playerBounty, true);
            Util::updateBounty($player);
        }

        $killstreak = $session->data["killstreak"];
        $session->data["killstreak"] = 0;

        $cause = $player->getLastDamageCause();

        if ($cause instanceof EntityDamageByEntityEvent) {
            $damager = $cause->getDamager();

            if ($damager instanceof Player) {
                LastInventory::saveOnlineInventory($player, $damager, $killstreak);

                $pot1 = Util::getItemCount($player, VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_HEALING()));
                $pot2 = Util::getItemCount($damager, VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_HEALING()));

                Main::getInstance()->getLogger()->info($player->getDisplayName() . " (" . $player->getName() . ") a été tué par " . $damager->getDisplayName() . " (" . $damager->getName() . ")");
                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§6" . $player->getDisplayName() . "[§7" . $pot1 . "§6] §fa été tué par le joueur §6" . $damager->getDisplayName() . "[§7" . $pot2 . "§6]");

                $damagerSession = Session::get($damager);

                $damagerSession->addValue("kill", 1);
                $damagerSession->addValue("killstreak", 1);

                if (Faction::hasFaction($damager)) Faction::addPower($damagerSession->data["faction"], 6);
                if (Faction::hasFaction($player)) Faction::addPower($session->data["faction"], -4);

                $damagerKillstreak = $damagerSession->data["killstreak"];

                if ($playerBounty > 0) {
                    $damagerSession->addValue("money", $playerBounty);
                    Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§6" . $damager->getName() . " §fvient de remporter un prime de §6" . $playerBounty . " pièce(s) §fen tuant §6" . $player->getName() . " §f!");
                }

                if ($damagerKillstreak % 5 == 0) {
                    $bounties = [5000, 6000, 7000, 8000, 9000, 10000];
                    $bountyToAdd = $bounties[array_rand($bounties)];
                    $damagerSession->addValue("bounty", $bountyToAdd);
                    Util::updateBounty($damager);
                    Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§6" . $damager->getName() . " §fa fait §6" . $damagerSession->data["killstreak"] . " §fkills sans mourrir ! Sa mort est désormais mise à prix à §6" . Session::get($damager)->data["bounty"] . " pièce(s) §8(§7+" . $bountyToAdd . "§8) §f!");
                }

                $item = $damager->getInventory()->getItemInHand();

                $enchantmentIdMap = EnchantmentIdMap::getInstance();
                $looter = $enchantmentIdMap->fromId(EnchantmentIds::LOOTER);
                $ares = $enchantmentIdMap->fromId(EnchantmentIds::ARES);

                if ($item->hasEnchantment($looter)) {
                    $enchantLevel = $item->getEnchantment($looter)?->getLevel();
                    $multiplier = 0.04 * $enchantLevel;
                    $playerMoney = $session->data["money"];
                    $moneyToLoot = round($playerMoney * $multiplier);

                    $formattedEnchant = "Pilleur " . Util::formatToRomanNumber($enchantLevel);

                    $session->addValue("money", $moneyToLoot, true);
                    $player->sendMessage(Util::PREFIX . "§6" . $damager->getName() . " §fvous a volé §6" . $moneyToLoot . " pièce(s) §fà cause de l'enchantement §6" .$formattedEnchant . " §fsur son épée !");

                    $damagerSession->addValue("money", $moneyToLoot);
                    $damager->sendMessage(Util::PREFIX . "§fVous avez volé §6" . $moneyToLoot . " pièce(s) §fà §6" . $player->getName() . " §fgrâce à votre enchantement §6" . $formattedEnchant . " §f!");
                }

                if ($item->hasEnchantment($ares)) {
                    $updatedItem = clone $item;

                    if (!is_null($updatedItem->getNamedTag()->getTag("kills"))) {
                        $kills = $updatedItem->getNamedTag()->getInt("kills");
                        $updatedItem->getNamedTag()->setInt("kills", ($updatedKills = $kills + 1));
                        $updatedItem->setCustomName("§r§bÉpée de " . $damager->getName() . " §8(§7" . $updatedKills . " kill(s)§8)");
                    } else {
                        $updatedItem->getNamedTag()->setInt("kills", 1);
                        $updatedItem->setCustomName("§r§bÉpée de " . $damager->getName() . " §8(§71 kill§8)");
                    }

                    $damager->getInventory()->setItemInHand($updatedItem);
                }

                return;
            }
        } else {
            Main::getInstance()->getLogger()->info($player->getDisplayName() . " (" . $player->getName() . ") est mort");
        }

        LastInventory::saveOnlineInventory($player, null, $killstreak);
    }

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
            $command = match ($item->getCustomName()) {
                "§r" . Util::PREFIX . "Vanish §6§l«" => "/vanish",
                "§r" . Util::PREFIX . "Random Tp §6§l«" => "/randomtp",
                "§r" . Util::PREFIX . "Spectateur §6§l«" => "/spec",
                default => null
            };

            if ($command !== null) {
                $player->chat($command);
            }
        }

        $executePp = PartnerItems::executeInteractPartnerItem($player, $event);
        $executePack = Pack::executeInteractPackItem($player, $event);

        if ($executePack || $executePp) {
            return;
        } else if ($item->equals(VanillaItems::SNOWBALL())) {
            $event->cancel();
            return;
        }

        if ($item instanceof EnderPearl) {
            if ($session->inCooldown("enderpearl")) {
                $player->sendMessage(Util::PREFIX . "Veuillez attendre §6" . ($session->getCooldownData("enderpearl")[0] - time()) . " §fsecondes avant de relancer une nouvelle perle");
                $event->cancel();
            } else {
                $position = $player->getPosition();

                if (Util::insideZone($player->getPosition(), "warzone") && $position->getY() <= 62) {
                    $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas lancer de perle dans les backrooms");
                    $event->cancel();
                    return;
                }

                if ($session->inCooldown("_antipearl")) {
                    $player->sendTip(Util::PREFIX . "Veuillez attendre §6" . ($session->getCooldownData("_antipearl")[0] - time()) . " §fsecondes avant de relancer une nouvelle perle");
                    $event->cancel();
                    return;
                } else if (!is_null($item->getNamedTag()->getTag("partneritem"))) {
                    $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas utiliser cette perle");
                    $event->cancel();
                    return;
                } else if (Util::isPlayerAimOnAntiBack($player)) {
                    $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas perle en visant un bloc antiback");
                    $event->cancel();
                    return;
                }

                $session->setCooldown("enderpearl", 15, [$player->getPosition()]);
            }
        }
    }

    public function onConsume(PlayerItemConsumeEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $event->getItem();

        $session = Session::get($player);

        if ($item->equals(VanillaItems::RAW_FISH())) {
            if ($session->inCooldown("cookie_combined")) {
                $player->sendMessage(Util::PREFIX . "Veuillez attendre §6" . ($session->getCooldownData("cookie_combined")[0] - time()) . " §fsecondes avant de remanger un cookie combiné");
                $event->cancel();
            } else {
                $player->getEffects()->add(new EffectInstance(VanillaEffects::ABSORPTION(), (10 * 20), 0, false));
                $player->getEffects()->add(new EffectInstance(VanillaEffects::REGENERATION(), (10 * 20), 0, false));
                $player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), (240 * 20), 0, false));
                $player->getEffects()->add(new EffectInstance(VanillaEffects::STRENGTH(), (240 * 20), 0, false));

                $session->setCooldown("cookie_combined", 25);
            }
        } else if ($item->equals(VanillaItems::COOKED_FISH())) {
            if ($session->inCooldown("cookie_regeneration")) {
                $player->sendMessage(Util::PREFIX . "Veuillez attendre §6" . ($session->getCooldownData("cookie_regeneration")[0] - time()) . " §fsecondes avant de remanger un cookie de regeneration");
                $event->cancel();
            } else {
                $player->getEffects()->add(new EffectInstance(VanillaEffects::REGENERATION(), (10 * 20), 0, false));
                $session->setCooldown("cookie_regeneration", 25);
            }
        } else if ($item->equals(VanillaItems::RAW_SALMON())) {
            if ($session->inCooldown("cookie_speed")) {
                $player->sendMessage(Util::PREFIX . "Veuillez attendre §6" . ($session->getCooldownData("cookie_speed")[0] - time()) . " §fsecondes avant de remanger un cookie de vitesse");
                $event->cancel();
            } else {
                $player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), (240 * 20), 0, false));
                $session->setCooldown("cookie_speed", 25);
            }
        } else if ($item->equals(VanillaItems::COOKED_SALMON())) {
            if ($session->inCooldown("cookie_strength")) {
                $player->sendMessage(Util::PREFIX . "Veuillez attendre §6" . ($session->getCooldownData("cookie_strength")[0] - time()) . " §fsecondes avant de remanger un cookie de force");
                $event->cancel();
            } else {
                $player->getEffects()->add(new EffectInstance(VanillaEffects::STRENGTH(), (240 * 20), 0, false));
                $session->setCooldown("cookie_strength", 25);
            }
        } else if ($item->equals(VanillaItems::GOLDEN_APPLE()) || $item->equals(VanillaItems::GOLDEN_CARROT())) {
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

            if ($action instanceof SlotChangeAction && ($staff || $player->hasNoClientPredictions())) {
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

    public function onPlace(BlockPlaceEvent $event): void
    {
        $player = $event->getPlayer();

        if (Session::get($player)->data["staff_mod"][0]) {
            $event->cancel();
            return;
        }

        foreach ($event->getTransaction()->getBlocks() as [$x, $y, $z, $block]) {
            if (!Faction::canBuild($player, $block, "place")) {
                $event->cancel();
                Util::antiBlockGlitch($player);
            }
        }
    }

    public function onSpread(BlockSpreadEvent $event): void
    {
        $source = $event->getSource();

        $sourcePos = $source->getPosition();
        $blockPos = $event->getBlock()->getPosition();

        if ($source instanceof Fire || $event->getBlock() instanceof Fire) {
            $event->cancel();
            return;
        }

        if ($source instanceof Lava && $sourcePos->getY() !== $blockPos->getY()) {
            $event->cancel();
        } else if ($source instanceof Liquid && $blockPos->getWorld() === Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()) {
            if (Faction::inClaim($sourcePos->getX(), $sourcePos->getZ()) !== Faction::inClaim($blockPos->getX(), $blockPos->getZ())) {
                $event->cancel();
            }
        }
    }

    public function onBucket(PlayerBucketEvent $event): void
    {
        $player = $event->getPlayer();

        if (!Faction::canBuild($player, $event->getBlockClicked(), "place")) {
            $event->cancel();
        } else if (Session::get($player)->data["staff_mod"][0]) {
            $event->cancel();
        }
    }

    public function onMissSwing(PlayerMissSwingEvent $event): void
    {
        $player = $event->getPlayer();
        $player->broadcastAnimation(new ArmSwingAnimation($player), $player->getViewers());
        $event->cancel();
    }

    public function onTrampleFarmland(EntityTrampleFarmlandEvent $event): void
    {
        $event->cancel();
    }

    public function onBreak(BlockBreakEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        $target = clone $block;
        $drop = true;

        $session = Session::get($player);

        if (!$player->isCreative() && $player->getPosition()->getWorld()->getFolderName() === "mine" && $player->getPosition()->getFloorX() > 10000) {
            $event->cancel();
            $drop = false;

            $event->setDrops([$block->asItem()->setCount(1)]);
            $event->setXpDropAmount(0);
        } else if ($player->getPosition()->getWorld()->getFolderName() !== "mine" && !Faction::canBuild($player, $block, "break")) {
            if ($block->isFullCube()) {
                Util::antiBlockGlitch($player);
            }

            $event->cancel();
            return;
        }

        if ($session->data["staff_mod"][0]) {
            $event->cancel();
            return;
        } else if ($session->data["cobblestone"] === false && ($block->hasSameTypeId(VanillaBlocks::COBBLESTONE()) || $block->hasSameTypeId(VanillaBlocks::STONE()))) {
            $event->setDrops([]);
        }

        if (!$player->isCreative() && $block->getPosition()->getWorld()->getFolderName() === "mine" && $player->getPosition()->getFloorX() < 10000) {
            $respawn = 0;
            $bedrock = false;

            if ($block->hasSameTypeId(VanillaBlocks::COCOA_POD())) {
                $respawn = 15;

                if ($block instanceof CocoaBlock) {
                    $block = $block->setAge(CocoaBlock::MAX_AGE);
                }

                $cookies = [
                    VanillaItems::COOKED_FISH(),
                    VanillaItems::COOKED_SALMON(),
                    VanillaItems::RAW_SALMON()
                ];

                $event->setDrops([$cookies[array_rand($cookies)]]);
            } else if ($block->hasSameTypeId(VanillaBlocks::DEEPSLATE_EMERALD_ORE()) || $block->hasSameTypeId(VanillaBlocks::ANCIENT_DEBRIS())) {
                $respawn = 15;
                $bedrock = true;

                if ($block->hasSameTypeId(VanillaBlocks::ANCIENT_DEBRIS())) {
                    $event->setDrops([VanillaItems::NETHERITE_INGOT()]);
                } else if ($block->hasSameTypeId(VanillaBlocks::DEEPSLATE_EMERALD_ORE())) {
                    $emerald = VanillaItems::GOLD_NUGGET()->setCount(mt_rand(1, 4));

                    if (mt_rand(0, 250) === 1) {
                        $event->setDrops([$emerald, VanillaItems::RABBIT_FOOT()]);
                    } else {
                        $event->setDrops([$emerald]);
                    }
                }
            } else if ($block->hasSameTypeId(VanillaBlocks::NETHER_GOLD_ORE())) {
                $respawn = 40;
                $bedrock = true;

                $items = [
                    ExtraVanillaItems::MINER_HELMET(),
                    VanillaItems::COOKED_FISH(),
                    VanillaItems::COOKED_SALMON(),
                    VanillaItems::RAW_SALMON(),
                    VanillaBlocks::REDSTONE()->asItem(),
                    VanillaBlocks::CHISELED_NETHER_BRICKS()->asItem(),
                    VanillaBlocks::SUNFLOWER()->asItem(),
                    VanillaBlocks::FLOWERING_AZALEA_LEAVES()->asItem(),
                    VanillaItems::EXPERIENCE_BOTTLE()->setCount(3),
                    VanillaBlocks::LAPIS_LAZULI()->asItem(),
                    ExtraVanillaItems::NETHERITE_DRILL(),
                    ExtraVanillaItems::POTION_LAUNCHER(),
                    VanillaItems::NETHERITE_INGOT(),
                    VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::BROWN())->asItem(),
                    VanillaItems::CARROT(),
                    VanillaItems::POTATO(),
                    VanillaItems::BEETROOT(),
                    VanillaItems::WHEAT_SEEDS(),
                    VanillaItems::BEETROOT_SEEDS(),
                    VanillaBlocks::STONE()->asItem(),
                    VanillaBlocks::COBBLESTONE()->asItem(),
                    VanillaBlocks::DIRT()->asItem(),
                    VanillaBlocks::GLASS()->asItem(),
                    VanillaItems::BAMBOO(),
                    VanillaItems::MELON(),
                    VanillaItems::SWEET_BERRIES()
                ];

                $player->broadcastSound(new AmethystBlockChimeSound());
                $event->setDrops([$items[array_rand($items)]]);
            } else if ($block->hasSameTypeId(VanillaBlocks::WHEAT())) {
                $respawn = 15;

                if ($block instanceof Wheat) {
                    $block = $block->setAge(Crops::MAX_AGE);
                }

                $session->addValue("money", ($rand = mt_rand(1, 10)));
                $player->sendTip("+ §6" . $rand . " §fPièces §6+");

                $event->setDrops([]);
            } else {
                $event->setDrops([]);
                $event->setXpDropAmount(0);
            }

            if ($respawn > 0) {
                $item = $event->getItem();
                $position = $target->getPosition();

                $replace = $bedrock ? VanillaBlocks::BEDROCK() : VanillaBlocks::AIR();

                if ($item instanceof Durable) {
                    $item->applyDamage(1);
                }

                $player->getInventory()->setItemInHand($item);
                $position->getWorld()->setBlock($position, $replace, false);

                PlayerTask::$blocks[] = [time() + $respawn, $position, $block];

                $position->getWorld()->addSound($position, new BlockBreakSound($target));
                $position->getWorld()->addParticle($position->add(0.5, 0.5, 0.5), new BlockBreakParticle($target));
            }

            $event->cancel();
        }

        if (!$player->isCreative() && $target->hasSameTypeId(VanillaBlocks::TRAPPED_CHEST())) {
            $event->setDrops([
                VanillaBlocks::EMERALD()->asItem()->setCount(mt_rand(3, 5))
            ]);
        }

        foreach ($event->getDrops() as $item) {
            Util::addItem($player, $item, !$drop);
        }

        if ($event->getXpDropAmount() > 0) {
            $player->getXpManager()->addXp($event->getXpDropAmount());
        }

        $event->setDrops([]);
        $event->setXpDropAmount(0);
    }

    public function onHitByProjectile(ProjectileHitEntityEvent $event): void
    {
        $player = $event->getEntityHit();

        $entity = $event->getEntity();
        $damager = $entity->getOwningEntity();

        if ($player instanceof Player && $damager instanceof Player) {
            $damagerPos = $damager->getPosition();
            $playerPos = $player->getPosition();

            if (Util::insideZone($damagerPos, "spawn") || Util::insideZone($playerPos, "spawn")) {
                return;
            }

            if ($entity instanceof SwitcherEntity) {
                if (Session::get($damager)->inCooldown("teleportation_switch")) {
                    $damager->sendMessage(Util::PREFIX . "Vous ne pouvez pas vous téléporté puis switch un joueur");
                    return;
                }

                $player->teleport($damagerPos);
                $damager->teleport($playerPos);

                $player->broadcastSound(new EndermanTeleportSound());
                $player->broadcastSound(new EndermanTeleportSound());

                $damager->sendMessage(Util::PREFIX . "Vous avez été switch avec le joueur §6" . $player->getDisplayName());
                $player->sendMessage(Util::PREFIX . "Vous avez été switch avec le joueur §6" . $damager->getDisplayName());
            } else if ($entity instanceof AntiBackBallEntity) {
                $player->setNoClientPredictions();

                $damager->sendMessage(Util::PREFIX . "Vous avez touché §6" . $player->getDisplayName() . " §favec votre antiback ball, il est donc freeze pendant §62 §fsecondes");
                $player->sendMessage(Util::PREFIX . "Vous avez été touché par une antiback ball par §6" . $damager->getDisplayName() . " §fvous êtes donc freeze pendant §62 §fsecondes");

                Session::get($damager)->setCooldown("combat", 30, [$player->getName()]);
                Session::get($player)->setCooldown("combat", 30, [$damager->getName()]);

                Main::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player) {
                    if ($player->isOnline()) {
                        $player->setNoClientPredictions(false);
                    }
                }), 2 * 20);
            }
        }
    }

    public function onCommand(CommandEvent $event): void
    {
        $sender = $event->getSender();

        $command = explode(" ", $event->getCommand());
        Main::getInstance()->getLogger()->info("[" . $sender->getName() . "] " . implode(" ", $command));

        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->inCooldown("cmd")) {
                $event->cancel();
            } else {
                if (!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
                    $session->setCooldown("cmd", 1);
                }
            }

            if ($sender->hasNoClientPredictions()) {
                $event->cancel();
                return;
            }

            $command[0] = strtolower($command[0]);
            $event->setCommand(implode(" ", $command));
        }
    }

    public function onPlayerSave(PlayerDataSaveEvent $event): void
    {
        $player = $event->getPlayer();

        if ($player instanceof Player) {
            $session = Session::get($player);
            $session->saveSessionData(false);
        }
    }

    public function onPreLogin(PlayerPreLoginEvent $event): void
    {
        $username = $event->getPlayerInfo()->getUsername();

        foreach (Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getEntities() as $entity) {
            if ($entity instanceof LogoutEntity) {
                $name = $entity->player;
                $name = is_null($name) ? "" : $name;

                if (strtolower($username) === strtolower($name)) {
                    $entity->killed = true;
                    $entity->flagForDespawn();
                }
            }
        }
    }

    public function onGrow(BlockGrowEvent $event): void
    {
        if ($event->getBlock()->getPosition()->getWorld()->getFolderName() === "mine") {
            $event->cancel();
        }
    }

    public function onUpdate(BlockUpdateEvent $event): void
    {
        if ($event->getBlock()->getPosition()->getWorld()->getFolderName() === "mine") {
            $event->cancel();
        }
    }

    public function onDamage(EntityDamageEvent $event): void
    {
        $entity = $event->getEntity();

        if (!$event->isCancelled() && $event->getModifier(EntityDamageEvent::MODIFIER_PREVIOUS_DAMAGE_COOLDOWN) < 0.0) {
            $event->cancel();
            return;
        }

        if ($entity instanceof Player) {
            $entitySession = Session::get($entity);

            if ($event->getCause() === EntityDamageEvent::CAUSE_VOID) {
                $entity->teleport($entity->getPosition()->getWorld()->getSpawnLocation());
                $event->cancel();
                return;
            } else if (
                $event->getCause() === EntityDamageEvent::CAUSE_FALL ||
                $event->getCause() === EntityDamageEvent::CAUSE_SUFFOCATION ||
                Util::insideZone($entity->getPosition(), "spawn") ||
                $entitySession->data["staff_mod"][0] ||
                str_starts_with($entity->getPosition()->getWorld()->getFolderName(), "box-") ||
                $entity->getPosition()->getWorld()->getFolderName() === "mine"
            ) {
                $event->cancel();
            }

            if ($event instanceof EntityDamageByEntityEvent) {
                $damager = $event->getDamager();
                if ($damager instanceof Player) {
                    if (Util::insideZone($damager->getPosition(), "spawn")) {
                        $event->cancel();
                    }

                    $damagerSession = Session::get($damager);

                    if ($damagerSession->data["staff_mod"][0]) {
                        $message = match ($damager->getInventory()->getItemInHand()->getCustomName()) {
                            "§r" . Util::PREFIX . "Sanction §6§l«" => "custom",
                            "§r" . Util::PREFIX . "Alias §6§l«" => "/alias \"" . $entity->getName() . "\"",
                            "§r" . Util::PREFIX . "Freeze §6§l«" => "/freeze \"" . $entity->getName() . "\"",
                            "§r" . Util::PREFIX . "Invsee §6§l«" => "/invsee \"" . $entity->getName() . "\"",
                            "§r" . Util::PREFIX . "Ecsee §6§l«" => "/ecsee \"" . $entity->getName() . "\"",
                            default => null
                        };

                        if ($message === "custom") {
                            if ($damager->getInventory()->getItemInHand()->getCustomName() === "§r" . Util::PREFIX . "Knockback 2 §e§l«") {
                                return;
                            }

                            Sanction::chooseSanction($damager, $entity->getName());
                        } else {
                            if (!is_null($message)) {
                                $damager->chat($message);
                            } else {
                                $damager->sendMessage("Vous venez de taper le joueur §6" . $entity->getName());
                            }
                        }

                        $event->cancel();
                        return;
                    }

                    if ($event->isCancelled() || Faction::hasFaction($damager) && Faction::hasFaction($entity) && $damagerSession->data["faction"] === $entitySession->data["faction"] || $entity->isFlying() || $entity->getAllowFlight()) {
                        $event->cancel();
                        return;
                    }
                    if ($entity->getGamemode() === GameMode::CREATIVE() || $damager->getGamemode() === GameMode::CREATIVE() || $entity->hasNoClientPredictions()) {
                        return;
                    }

                    if ($damager->getInventory()->getItemInHand()->getTypeId() === VanillaItems::NETHERITE_SWORD()->getTypeId()) {
                        $event->setBaseDamage(7);
                    } else if ($damager->getInventory()->getItemInHand()->getTypeId() === VanillaItems::DIAMOND_SWORD()->getTypeId()) {
                        $event->setBaseDamage(6);
                    }

                    PartnerItems::executeHitPartnerItem($damager, $entity);

                    $damagerSession->setCooldown("combat", 20, [$entity->getName()]);
                    $entitySession->setCooldown("combat", 20, [$damager->getName()]);

                    $event->setKnockback(0.38);
                    $event->setAttackCooldown(8);

                    $damagerSession->data["last_hit"] = [$entity->getName(), time()];

                    if ($entitySession->inCooldown("_focusmode") && $damager->getName() === $entitySession->getCooldownData("_focusmode")[1]) {
                        $event->setBaseDamage($event->getBaseDamage() + (($event->getBaseDamage() / 100) * 15));
                    }

                    $entity->setScoreTag("§7" . round($entity->getHealth(), 2) . " §c❤");

                    if (!$event->isCancelled()) {

                        $item = $damager->getInventory()->getItemInHand();
                        $lightningStrike = EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::LIGHTNING_STRIKE);

                        if ($item->hasEnchantment($lightningStrike)) {
                            $level = $item->getEnchantment($lightningStrike)?->getLevel();
                            $chance = match ($level) {
                                1 => 300,
                                2 => 225,
                                3 => 150
                            };
                            if (mt_rand(0, $chance) < 1) {
                                $lightning = new LightningBolt($entity->getLocation());
                                $lightning->spawnToAll();

                                $entity->setLastDamageCause(new EntityDamageByEntityEvent($damager, $entity, $event::CAUSE_CUSTOM, 2));
                                $entity->setHealth(max($entity->getHealth() - 2, 0));

                                $hurtAnimation = new HurtAnimation($entity);
                                $viewers = array_merge($entity->getViewers(), $damager->getViewers());
                                NetworkBroadcastUtils::broadcastPackets(array_unique($viewers), $hurtAnimation->encode());
                                $entity->getWorld()->broadcastPacketToViewers($entity->getPosition()->asVector3(), LevelSoundEventPacket::create(LevelSoundEvent::THUNDER, $entity->getLocation(), -1, "minecraft:lightning_bolt", false, false));

                                $entity->sendMessage(Util::PREFIX . "§6" . $damager->getName() . " §fvient de vous envoyer un éclair dessus grâce à son enchantement §6Foudroiement §f!");
                            }
                        }

                    }
                }
            }
        }
    }

    public function onCraft(CraftItemEvent $event): void
    {
        $input = $event->getInputs();
        $player = $event->getPlayer();

        foreach ($input as $item) {
            if (!is_null($item->getNamedTag()->getTag("partneritem"))) {
                $event->cancel();
                $player->removeCurrentWindow();

                $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas utiliser des partneritems pour craft des items ou autre");
                break;
            }
        }
    }

    public function onItemSpawn(ItemSpawnEvent $event): void
    {
        $entity = $event->getEntity();
        $entity->setDespawnDelay(intval(15 * Main::getInstance()->getServer()->getTicksPerSecondAverage()));
    }

    public function onDataPacketSend(DataPacketSendEvent $event): void
    {
        $packets = $event->getPackets();
        foreach ($packets as $packet) {
            switch ($packet) {
                case $packet instanceof InventorySlotPacket:
                    $packet->item = new ItemStackWrapper($packet->item->getStackId(), Util::displayEnchants($packet->item->getItemStack()));
                    break;
                case $packet instanceof InventoryContentPacket:
                    foreach ($packet->items as $i => $item) {
                        $packet->items[$i] = new ItemStackWrapper($item->getStackId(), Util::displayEnchants($item->getItemStack()));
                    }
                    break;
                case $packet instanceof SetTimePacket:
                    $packet->time = 12500; // on sait jamais parfois le stoptime il a la flemme
                    break;
                case $packet instanceof StartGamePacket:
                    $packet->levelSettings->muteEmoteAnnouncements = true;
                    break;
            }
        }
    }

    /*public function onDataPacketReceive(DataPacketReceiveEvent $event): void
    {
        $packet = $event->getPacket();
        if ($packet instanceof InventoryTransactionPacket) {
            $transactionData = $packet->trData;
            foreach ($transactionData->getActions() as $action) {
                $action->oldItem = new ItemStackWrapper($action->oldItem->getStackId(), Util::filterDisplayedEnchants($action->oldItem->getItemStack()));
                $action->newItem = new ItemStackWrapper($action->newItem->getStackId(), Util::filterDisplayedEnchants($action->newItem->getItemStack()));
            }
        }
    }*/

    public function onDataPacketDecode(DataPacketDecodeEvent $event): void
    {
        $origin = $event->getOrigin();
        $packetId = $event->getPacketId();
        $packetBuffer = $event->getPacketBuffer();
        if (strlen($packetBuffer) > 8096 and $packetId !== ProtocolInfo::LOGIN_PACKET) {
            Main::getInstance()->getLogger()->warning("ID de paquet non décodé: $packetId (" . strlen($packetBuffer) . ") venant de : " . (($player = $origin->getPlayer()) instanceof Player ? $player->getName() : $origin->getIp()));
            $origin->disconnect("§cUne erreur est survenue lors de l'encodage d'un paquet.");
            $event->cancel();
        }
    }
}
