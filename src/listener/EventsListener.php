<?php /** @noinspection PhpUnused */

namespace Kitmap\listener;

use Element\item\ExtraVanillaItems;
use Element\util\data\ItemTypeNames;
use Kitmap\command\player\{Anvil, Enchant, rank\Enderchest};
use Kitmap\command\staff\{Ban, LastInventory, Question, Vanish};
use Kitmap\command\util\Bienvenue;
use Kitmap\enchantment\EnchantmentIds;
use Kitmap\entity\{AntiBackBallEntity, LightningBolt, LogoutEntity, SwitcherEntity};
use Kitmap\handler\{Cache, Faction, Jobs, Pack, PartnerItems, Rank, Sanction};
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\task\repeat\GamblingTask;
use Kitmap\task\repeat\PlayerTask;
use Kitmap\Util;
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
    NetherWartPlant,
    SweetBerryBush,
    Trapdoor,
    utils\DyeColor,
    VanillaBlocks,
    Wheat};
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\animation\HurtAnimation;
use pocketmine\entity\effect\{EffectInstance, VanillaEffects};
use pocketmine\entity\object\ItemEntity;
use pocketmine\event\block\{BlockBreakEvent, BlockMeltEvent, BlockPlaceEvent, BlockSpreadEvent, LeavesDecayEvent};
use pocketmine\event\entity\{EntityDamageByEntityEvent,
    EntityDamageEvent,
    EntityItemPickupEvent,
    EntityShootBowEvent,
    EntityTeleportEvent,
    EntityTrampleFarmlandEvent,
    ItemSpawnEvent,
    ProjectileHitEntityEvent};
use pocketmine\event\inventory\{InventoryOpenEvent, InventoryTransactionEvent};
use pocketmine\event\Listener;
use pocketmine\event\player\{PlayerBucketEvent,
    PlayerChatEvent,
    PlayerDataSaveEvent,
    PlayerDeathEvent,
    PlayerDropItemEvent,
    PlayerInteractEvent,
    PlayerItemUseEvent,
    PlayerJoinEvent,
    PlayerMissSwingEvent,
    PlayerPreLoginEvent,
    PlayerQuitEvent,
    PlayerRespawnEvent};
use pocketmine\event\server\CommandEvent;
use pocketmine\event\server\DataPacketDecodeEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\CallbackInventoryListener;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\{Axe, Bucket, Durable, Hoe, Item, PaintingItem, PotionType, Shovel, Stick, VanillaItems};
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
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

class EventsListener implements Listener
{
    public function onInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $item = $event->getItem();

        $position = $block->getPosition();

        if ($item->equals(VanillaItems::FLINT_AND_STEEL(), false, false)) {
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
            Util::removeCurrentWindow($player);

            Anvil::openAnvil($player);
        } else if (!$player->isSneaking() && $event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK && $block->hasSameTypeId(VanillaBlocks::ENCHANTING_TABLE())) {
            $event->cancel();
            Util::removeCurrentWindow($player);

            Enchant::openEnchantTable($player, false);
        }

        if (PlayerInteractEvent::RIGHT_CLICK_BLOCK && $position->getWorld() === Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()) {
            $format = $position->x . ":" . $position->y . ":" . $position->z;
            $pack = Cache::$config["enderchest"][$format] ?? null;

            if (!is_null($pack)) {
                $event->cancel();

                Util::removeCurrentWindow($player);
                Pack::openPackCategoryUI($player, $pack);
            }
        }
    }

    public function onChat(PlayerChatEvent $event): void
    {
        $player = $event->getPlayer();
        $message = TextFormat::clean($event->getMessage());

        $session = Session::get($player);

        if (str_contains($message, "@here") && !$player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            $event->cancel();
            $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas utiliser §q@here §fdans votre message");
            return;
        }

        if (Question::$currentEvent !== 0) {
            $valid = false;

            switch (Question::$currentEvent) {
                case 1:
                    if ($event->getMessage() === Question::$currentReply) {
                        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§q" . $player->getDisplayName() . " §fa gagné §q5k$ §fen ayant réécrit le code §q" . Question::$currentReply . " §fen premier !");
                        $valid = true;
                    }
                    break;
                case 2:
                    if (strtolower($event->getMessage()) === Question::$currentReply) {
                        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§q" . $player->getDisplayName() . " §fa gagné §q5k$ §fen ayant trouver le mot §q" . Question::$currentReply . " §fen premier !");
                        $valid = true;
                    }
                    break;
                case 3:
                    if ($event->getMessage() === strval(Question::$currentReply)) {
                        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§q" . $player->getDisplayName() . " §fa gagné §q5k$ §fen ayant répondu au calcul §q" . Question::$currentReply . " §fen premier !");
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
            Faction::broadcastMessage($faction, "§q[§fF§q] §f" . $player->getName() . " " . Util::PREFIX . $message);

            return;
        } else if ($session->inCooldown("mute")) {
            $format = Util::formatDurationFromSeconds($session->getCooldownData("mute")[0] - time());
            $player->sendMessage(Util::PREFIX . "Vous êtes mute, temps restant: §q" . $format);

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

        if (Ban::checkBan($event)) {
            return;
        }

        Main::getInstance()->getServer()->broadcastTip("§a+ " . $player->getName() . " +");

        if (Faction::hasFaction($player)) {
            Cache::$factions[$session->data["faction"]]["activity"][date("m-d")] = $player->getName();
            Faction::broadcastMessage($session->data["faction"], "§q[§fF§q] §fLe joueur de votre faction §q" . $player->getName() . " §fvient de se connecter");
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

            Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§q" . $player->getName() . " §fa rejoint le serveur pour la §qpremière §ffois ! Souhaitez lui la §qbienvenue §favec la commande §q/bvn §f(#§q" . $count . "§f)!");

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

        if ($session->data["snow"]) {
            $player->getNetworkSession()->sendDataPacket(LevelEventPacket::create(LevelEvent::START_RAIN, 10000, null));
        }

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
            str_starts_with($from->getWorld()->getFolderName(), "island-") &&
            !str_starts_with($to->getWorld()->getFolderName(), "island-")
        ) {
            if (!Session::get($entity)->data["staff_mod"][0] && !$entity->isCreative()) {
                $entity->setFlying(false);
                $entity->setAllowFlight(false);
            }
        }
    }

    public function onMeltEvent(BlockMeltEvent $event): void
    {
        if ($event->getBlock()->getPosition()->getWorld() === Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()) {
            $event->cancel();
        }
    }

    public function onRespawn(PlayerRespawnEvent $event): void
    {
        Util::givePlayerPreferences($event->getPlayer());
    }

    public function onQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();
        Util::removeCurrentWindow($player);

        Main::getInstance()->getServer()->broadcastTip("§c- " . $player->getName() . " -");
        $event->setQuitMessage("");

        if (in_array($player->getName(), GamblingTask::$players)) {
            $ev = new PlayerDeathEvent($player, [], 0, "");
            $ev->call();
        } else if (Util::getTpTime($player) > 0) {
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

        if (in_array($player->getName(), GamblingTask::$players)) {
            $otherPlayerName = (GamblingTask::$players[0] === $player->getName()) ? GamblingTask::$players[1] : GamblingTask::$players[0];
            GamblingTask::stop($otherPlayerName);

            if (Faction::hasFaction($player)) {
                Faction::addPower($session->data["faction"], -4);
            }

            $cause = $player->getLastDamageCause();

            if (!$cause instanceof EntityDamageByEntityEvent) {
                return;
            }

            $damager = $cause->getDamager();

            if ($damager instanceof Player && Faction::hasFaction($damager)) {
                $damagerSession = Session::get($damager);
                Faction::addPower($damagerSession->data["faction"], 6);
            }

            return;
        }

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
                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§q" . $player->getDisplayName() . "[§7" . $pot1 . "§q] §fa été tué par le joueur §q" . $damager->getDisplayName() . "[§7" . $pot2 . "§q]");

                $damagerSession = Session::get($damager);

                $damagerSession->addValue("kill", 1);
                $damagerSession->addValue("killstreak", 1);

                if (Faction::hasFaction($damager)) Faction::addPower($damagerSession->data["faction"], 6);
                if (Faction::hasFaction($player)) Faction::addPower($session->data["faction"], -4);

                $damagerKillstreak = $damagerSession->data["killstreak"];

                if ($playerBounty > 0) {
                    $damagerSession->addValue("money", $playerBounty);
                    Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§q" . $damager->getName() . " §fvient de remporter un prime de §q" . $playerBounty . " pièce(s) §fen tuant §q" . $player->getName() . " §f!");
                }

                if ($damagerKillstreak % 5 == 0) {
                    $amount = Cache::$config["bounties"][array_rand(Cache::$config["bounties"])];
                    $damagerSession->addValue("bounty", $amount);

                    Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§q" . $damager->getName() . " §fa fait §q" . $damagerSession->data["killstreak"] . " §fkills sans mourrir ! Sa mort est désormais mise à prix à §q" . Session::get($damager)->data["bounty"] . " pièce(s) §8(§7+" . $amount . "§8) §f!");
                }

                Jobs::addXp($damager, "Hunter", 50 + $damagerSession->data["player"]["killstreak"]);

                $item = $damager->getInventory()->getItemInHand();

                $enchantmentIdMap = EnchantmentIdMap::getInstance();
                $looter = $enchantmentIdMap->fromId(EnchantmentIds::LOOTER);
                $ares = $enchantmentIdMap->fromId(EnchantmentIds::ARES);

                if ($item->hasEnchantment($looter)) {
                    $enchantLevel = $item->getEnchantment($looter)?->getLevel();
                    $moneyToLoot = round($session->data["money"] * (0.02 * $enchantLevel));

                    $session->addValue("money", $moneyToLoot, true);
                    $player->sendMessage(Util::PREFIX . "§q" . $damager->getName() . " §fvous a volé §q" . $moneyToLoot . " pièce(s) §fà cause de l'enchantement §qPilleur " . Util::formatToRomanNumber($enchantLevel) . " §fsur son épée !");

                    $damagerSession->addValue("money", $moneyToLoot);
                    $damager->sendMessage(Util::PREFIX . "§fVous avez volé §q" . $moneyToLoot . " pièce(s) §fà §q" . $player->getName() . " §fgrâce à votre enchantement §qPilleur " . Util::formatToRomanNumber($enchantLevel) . " §f!");
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
                "§r" . Util::PREFIX . "Vanish §q§l«" => "/vanish",
                "§r" . Util::PREFIX . "Random Tp §q§l«" => "/randomtp",
                "§r" . Util::PREFIX . "Spectateur §q§l«" => "/spec",
                default => null
            };

            if ($command !== null) {
                $player->chat($command);
            }
        }

        if ($event->isCancelled()) {
            return;
        }

        $executePp = PartnerItems::executeInteractPartnerItem($player, $event);
        $executePack = Pack::executeInteractPackItem($player, $event);

        if ($executePack || $executePp) {
            return;
        } else if ($item->equals(VanillaItems::SNOWBALL())) {
            $event->cancel();
            return;
        }

        if (!is_null($item->getNamedTag()->getTag("xp_bottle"))) {
            $xp = $item->getNamedTag()->getInt("xp_bottle");

            $player->getXpManager()->addXpLevels($xp);
            $player->getInventory()->removeItem($item->setCount(1));

            $player->sendMessage(Util::PREFIX . "§fVous venez de récupérer §q" . $xp . " §fniveaux d'expérience");

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
        $event->getPlayer()->broadcastAnimation(new ArmSwingAnimation($event->getPlayer()), $event->getPlayer()->getViewers());
        $event->cancel();
    }

    public function onTrampleFarmland(EntityTrampleFarmlandEvent $event): void
    {
        $event->cancel();
    }

    public function onDrop(PlayerDropItemEvent $event): void
    {
        if (in_array($event->getPlayer()->getName(), GamblingTask::$players)) {
            $event->cancel();
        }
    }

    public function onBreak(BlockBreakEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        $target = clone $block;
        $drop = true;

        $session = Session::get($player);

        /*if ($player->getInventory()->getItemInHand()->getTypeId() === VanillaItems::STONE_AXE()->getTypeId()) {
            if (AddClaims::addClaim($block->getPosition()->getX(), $block->getPosition()->getZ())) {
                $player->sendMessage(Util::PREFIX . "Chunk ajouté");
            }

            $event->cancel();
        }*/

        if (!$player->isCreative() && $player->getPosition()->getWorld()->getFolderName() === "mine" && $player->getPosition()->getFloorX() > 4500) {
            $event->cancel();
            $drop = false;

            if ($session->data["money"] >= 15) {
                $session->addValue("money", 15, true);
                $player->sendTip("§q- 15 pièces");

                $event->setDrops([$block->asItem()->setCount(1)]);
                $event->setXpDropAmount(0);
            } else {
                $player->sendTip("§qVous n'avez pas assez d'argent pour acheter les blocs (15 pièces/u)");
            }
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

        if (!$player->isCreative() && $block->getPosition()->getWorld()->getFolderName() === "mine" && $player->getPosition()->getFloorX() < 4500) {
            $respawn = 0;
            $bedrock = false;

            if ($block->hasSameTypeId(VanillaBlocks::COCOA_POD())) {
                $respawn = 15;

                if ($block instanceof CocoaBlock) {
                    $block = $block->setAge(CocoaBlock::MAX_AGE);
                }

                $cookies = [VanillaItems::COOKED_FISH(), VanillaItems::COOKED_SALMON(), VanillaItems::RAW_SALMON()];
                $event->setDrops([$cookies[array_rand($cookies)]]);
            } else if ($block->hasSameTypeId(VanillaBlocks::DEEPSLATE_EMERALD_ORE())) {
                $respawn = 15;
                $bedrock = true;

                if ($block->hasSameTypeId(VanillaBlocks::DEEPSLATE_EMERALD_ORE())) {
                    $emerald = Util::getItemByName(ItemTypeNames::EMERALD_NUGGET)->setCount(mt_rand(1, 5));
                    $event->setDrops([$emerald]);

                    Jobs::addXp($player, "Mineur", 15);
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
                    VanillaItems::NAUTILUS_SHELL(),
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

                Jobs::addXp($player, "Mineur", 5, false);
            } else if ($block->hasSameTypeId(VanillaBlocks::WHEAT())) {
                $respawn = 15;

                if ($block instanceof Wheat) {
                    $block = $block->setAge(Crops::MAX_AGE);
                }

                $session->addValue("money", ($rand = mt_rand(1, 10)));
                $player->sendTip("+ §q" . $rand . " §fPièces §q+");

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
                VanillaBlocks::EMERALD()->asItem()->setCount(mt_rand(3, 6))
            ]);
        }

        if ($target->hasSameTypeId(VanillaBlocks::COBBLESTONE()) || $target->hasSameTypeId(VanillaBlocks::STONE())) {
            Jobs::addXp($player, "Mineur", 1);
        } else if ($target->hasSameTypeId(VanillaBlocks::MELON())) {
            Jobs::addXp($player, "Farmeur", mt_rand(1, 3));
        }

        if ($target instanceof Crops && $target->getAge() === 7) {
            Jobs::addXp($player, "Farmeur", mt_rand(1, 3));
        }

        if (!$player->isCreative() && $target->hasSameTypeId(VanillaBlocks::COBBLESTONE()) && mt_rand(0, 20) == 0) {
            $event->setDrops([
                Util::getItemByName(ItemTypeNames::IRIS_DUST)->setCount(1)
            ]);
        }

        if (!$player->isCreative() && $target instanceof NetherWartPlant && $target->getAge() === NetherWartPlant::MAX_AGE && mt_rand(0, 30) == 0) {
            $event->setDrops([
                Util::getItemByName(ItemTypeNames::IRIS_DUST)->setCount(1)
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

    public function onLeavesDecay(LeavesDecayEvent $event): void
    {
        if ($event->getBlock()->getPosition()->getWorld() === Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()) {
            $event->cancel();
        }
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

                $damager->sendMessage(Util::PREFIX . "Vous avez été switch avec le joueur §q" . $player->getDisplayName());
                $player->sendMessage(Util::PREFIX . "Vous avez été switch avec le joueur §q" . $damager->getDisplayName());
            } else if ($entity instanceof AntiBackBallEntity) {
                $player->setNoClientPredictions();

                $damager->sendMessage(Util::PREFIX . "Vous avez touché §q" . $player->getDisplayName() . " §favec votre antiback ball, il est donc freeze pendant §q2 §fsecondes");
                $player->sendMessage(Util::PREFIX . "Vous avez été touché par une antiback ball par §q" . $damager->getDisplayName() . " §fvous êtes donc freeze pendant §q2 §fsecondes");

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

            if (in_array($sender->getName(), GamblingTask::$players)) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas executer de commande en plein gambling");
                $event->cancel();
                return;
            } else if ($sender->hasNoClientPredictions()) {
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
                (Util::insideZone($entity->getPosition(), "spawn") && !in_array($entity->getName(), GamblingTask::$players)) ||
                $entitySession->data["staff_mod"][0] ||
                str_starts_with($entity->getPosition()->getWorld()->getFolderName(), "island-") ||
                $entity->getPosition()->getWorld()->getFolderName() === "mine"
            ) {
                $event->cancel();
            }

            if ($event instanceof EntityDamageByEntityEvent) {
                $damager = $event->getDamager();

                if ($damager instanceof Player) {
                    if (Util::insideZone($damager->getPosition(), "spawn") && !in_array($damager->getName(), GamblingTask::$players)) {
                        $event->cancel();
                    }

                    $damagerSession = Session::get($damager);

                    if ($damagerSession->data["staff_mod"][0]) {
                        $message = match ($damager->getInventory()->getItemInHand()->getCustomName()) {
                            "§r" . Util::PREFIX . "Sanction §q§l«" => "custom",
                            "§r" . Util::PREFIX . "Alias §q§l«" => "/alias \"" . $entity->getName() . "\"",
                            "§r" . Util::PREFIX . "Freeze §q§l«" => "/freeze \"" . $entity->getName() . "\"",
                            "§r" . Util::PREFIX . "Invsee §q§l«" => "/invsee \"" . $entity->getName() . "\"",
                            "§r" . Util::PREFIX . "Ecsee §q§l«" => "/ecsee \"" . $entity->getName() . "\"",
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
                                $damager->sendMessage("Vous venez de taper le joueur §q" . $entity->getName());
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
                        goto skip;
                    }

                    if ($damager->getInventory()->getItemInHand() instanceof Axe) {
                        $event->setBaseDamage(max(1, $event->getBaseDamage() - 2));
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

                    $sup = "";

                    if (($bounty = $entitySession->data["bounty"]) > 0) {
                        $sup .= " §7| §q" . Util::formatNumberWithSuffix($bounty) . " \u{E102}";
                    }

                    $entity->setScoreTag("§7" . round($entity->getHealth(), 2) . " §c❤" . $sup);

                    $item = $damager->getInventory()->getItemInHand();
                    $lightningStrike = EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::LIGHTNING_STRIKE);

                    if ($item->hasEnchantment($lightningStrike)) {
                        $level = $item->getEnchantment($lightningStrike)?->getLevel();

                        $chance = match ($level) {
                            1 => 200,
                            2 => 150,
                            3 => 100
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

                            $entity->sendMessage(Util::PREFIX . "§q" . $damager->getName() . " §fvient de vous envoyer un éclair dessus grâce à son enchantement §qFoudroiement §f!");
                        }
                    }
                }
            }

            skip:

            if (!$event->isCancelled() && in_array($entity->getName(), GamblingTask::$players) && $event->getFinalDamage() >= $entity->getHealth()) {
                $ev = new PlayerDeathEvent($entity, [], 0, "");
                $ev->call();

                $event->setBaseDamage(0);
            }
        } else if ($entity instanceof ItemEntity) {
            $entity->teleport($entity->getWorld()->getSpawnLocation());

            if ($entity->getItem()->getTypeId() === VanillaBlocks::CACTUS()->asItem()->getTypeId()) {
                $entity->setMotion(new Vector3(0.3, 0.3, 0.3));
                $event->cancel();
            }
        }
    }

    public function onItemSpawn(ItemSpawnEvent $event): void
    {
        $entity = $event->getEntity();
        $entity->setDespawnDelay(intval(15 * Main::getInstance()->getServer()->getTicksPerSecondAverage()));
    }

    public function onDataPacketDecode(DataPacketDecodeEvent $event): void
    {
        $packetId = $event->getPacketId();
        $packetBuffer = $event->getPacketBuffer();

        if (strlen($packetBuffer) > 8096 && $packetId !== ProtocolInfo::LOGIN_PACKET) {
            $origin = $event->getOrigin();
            $event->cancel();

            Main::getInstance()->getLogger()->warning("ID de paquet non décodé: $packetId (" . strlen($packetBuffer) . ") venant de : " . $origin->getPlayer() instanceof Player ? $origin->getPlayer()->getName() : $origin->getIp());
            Main::getInstance()->getServer()->getNetwork()->blockAddress($origin->getIp(), 250);
        }
    }
}
