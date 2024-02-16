<?php /** @noinspection PhpUnused */

namespace Kitmap\listener;

use Kitmap\block\ExtraVanillaBlocks;
use Kitmap\command\player\rank\Enderchest;
use Kitmap\command\staff\{Ban, LastInventory, Question, Vanish};
use Kitmap\command\util\Bienvenue;
use Kitmap\entity\{AntiBackBall, LogoutNpc, SwitchBall};
use Kitmap\entity\Player as CustomPlayer;
use Kitmap\handler\{Cache, Faction, Jobs, Pack, PartnerItems, Rank, Sanction};
use Kitmap\item\Armor;
use Kitmap\item\ExtraVanillaItems;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\task\repeat\child\GamblingTask;
use Kitmap\task\repeat\PlayerTask;
use Kitmap\Util;
use pocketmine\block\{Anvil,
    Barrel,
    Block,
    CartographyTable,
    Chest,
    CraftingTable,
    Crops,
    Door,
    EnchantingTable,
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
    VanillaBlocks};
use pocketmine\block\tile\Chest as ChestTile;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\effect\{EffectInstance, VanillaEffects};
use pocketmine\entity\Living;
use pocketmine\entity\object\ItemEntity;
use pocketmine\event\block\{BlockBreakEvent,
    BlockGrowEvent,
    BlockMeltEvent,
    BlockPlaceEvent,
    BlockSpreadEvent,
    BlockUpdateEvent,
    LeavesDecayEvent};
use pocketmine\event\entity\{EntityDamageByEntityEvent,
    EntityDamageEvent,
    EntityItemPickupEvent,
    EntityShootBowEvent,
    EntityTeleportEvent,
    EntityTrampleFarmlandEvent,
    ItemSpawnEvent,
    ProjectileHitEntityEvent};
use pocketmine\event\inventory\{CraftItemEvent, InventoryOpenEvent, InventoryTransactionEvent, ItemDamageEvent};
use pocketmine\event\Listener;
use pocketmine\event\player\{PlayerBucketEvent,
    PlayerChatEvent,
    PlayerCreationEvent,
    PlayerDataSaveEvent,
    PlayerDeathEvent,
    PlayerDropItemEvent,
    PlayerExhaustEvent,
    PlayerInteractEvent,
    PlayerItemConsumeEvent,
    PlayerItemUseEvent,
    PlayerJoinEvent,
    PlayerJumpEvent,
    PlayerMissSwingEvent,
    PlayerPreLoginEvent,
    PlayerQuitEvent,
    PlayerRespawnEvent,
    PlayerToggleSneakEvent};
use pocketmine\event\server\CommandEvent;
use pocketmine\event\server\DataPacketDecodeEvent;
use pocketmine\event\world\ChunkLoadEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\CallbackInventoryListener;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\{Axe,
    Bucket,
    Durable,
    Hoe,
    Item,
    PaintingItem,
    PotionType,
    Shovel,
    Stick,
    TieredTool,
    VanillaItems};
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\{GameMode, Player};
use pocketmine\player\chat\LegacyRawChatFormatter;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\sound\BlockBreakSound;
use pocketmine\world\sound\EndermanTeleportSound;
use Symfony\Component\Filesystem\Path;

class EventsListener implements Listener
{
    public function onCreation(PlayerCreationEvent $event): void
    {
        $event->setPlayerClass(CustomPlayer::class);
    }

    public function onChunkLoad(ChunkLoadEvent $event): void
    {
        if (!$event->isNewChunk()) {
            return;
        }

        $chunkX = $event->getChunkX();
        $chunkZ = $event->getChunkZ();

        if ($chunkX !== 16 || $chunkZ !== 16 || !str_starts_with($event->getWorld()->getFolderName(), "island-")) {
            return;
        }

        list($x, $y, $z) = explode(":", Cache::$config["islands"][$event->getWorld()->getProvider()->getWorldData()->getGenerator()]["chest"]);

        $vector = new Vector3(intval($x), intval($y), intval($z));
        $tile = $event->getWorld()->getTile($vector);

        if ($tile instanceof ChestTile) {
            return;
        } else {
            $chest = new ChestTile($event->getWorld(), $vector);

            $chest->getInventory()->addItem(VanillaItems::WATER_BUCKET()->setCount(3));
            $chest->getInventory()->addItem(VanillaItems::LAVA_BUCKET()->setCount(2));
            $chest->getInventory()->addItem(VanillaBlocks::ICE()->asItem()->setCount(4));
            $chest->getInventory()->addItem(VanillaItems::BEETROOT_SEEDS()->setCount(7));
            $chest->getInventory()->addItem(VanillaItems::WHEAT_SEEDS()->setCount(9));
            $chest->getInventory()->addItem(VanillaItems::POTATO()->setCount(3));
            $chest->getInventory()->addItem(VanillaItems::BONE()->setCount(16));
            $chest->getInventory()->addItem(VanillaItems::BAMBOO()->setCount(25));
            $chest->getInventory()->addItem(VanillaBlocks::BARREL()->asItem()->setCount(2));

            $event->getWorld()->addTile($chest);
        }
    }

    public function onInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();

        $block = $event->getBlock();
        $item = $event->getItem();

        if (
            $event->getAction() === $event::RIGHT_CLICK_BLOCK &&
            (($block instanceof Door || $block instanceof Trapdoor || $block instanceof FenceGate || $block instanceof Furnace || $block instanceof SweetBerryBush || $block instanceof GlowLichen || $block instanceof CraftingTable || $block instanceof CartographyTable || $block instanceof Chest || $block instanceof Barrel || $block instanceof Hopper) || ($item instanceof Bucket || $item instanceof Hoe || $item instanceof Axe || $item instanceof Shovel || $item instanceof PaintingItem || $item instanceof Stick)) &&
            !Faction::canBuild($player, $block, "interact") &&
            !(Util::insideZone($player->getPosition(), "spawn") && ($block instanceof Anvil || $block instanceof EnchantingTable))
        ) {
            $event->cancel();

            if ($block instanceof Door || $block instanceof Trapdoor || $block instanceof FenceGate) {
                Util::antiBlockGlitch($player);
            }

            return;
        }

        if (!ExtraVanillaItems::getItem($item)->onInteract($event)) {
            ExtraVanillaBlocks::getBlock($block)->onInteract($event);
        }
    }

    public function onChat(PlayerChatEvent $event): void
    {
        $player = $event->getPlayer();
        $message = TextFormat::clean($event->getMessage());

        $session = Session::get($player);

        if (str_contains($message, "@here") && !$player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            $event->cancel();
            $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas utiliser §9@here §fdans votre message");
            return;
        }

        if (Question::$currentEvent !== 0) {
            $valid = false;

            switch (Question::$currentEvent) {
                case 1:
                    if ($event->getMessage() === Question::$currentReply) {
                        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§9" . $player->getDisplayName() . " §fa gagné §95k$ §fen ayant réécrit le code §9" . Question::$currentReply . " §fen premier !");
                        $valid = true;
                    }
                    break;
                case 2:
                    if (strtolower($event->getMessage()) === Question::$currentReply) {
                        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§9" . $player->getDisplayName() . " §fa gagné §95k$ §fen ayant trouver le mot §9" . Question::$currentReply . " §fen premier !");
                        $valid = true;
                    }
                    break;
                case 3:
                    if ($event->getMessage() === strval(Question::$currentReply)) {
                        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§9" . $player->getDisplayName() . " §fa gagné §95k$ §fen ayant répondu au calcul §9" . Question::$currentReply . " §fen premier !");
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
            Faction::broadcastMessage($faction, "§9[§fF§9] §f" . $player->getName() . " " . Util::PREFIX . $message);

            return;
        } else if ($session->inCooldown("mute")) {
            $format = Util::formatDurationFromSeconds($session->getCooldownData("mute")[0] - time());
            $player->sendMessage(Util::PREFIX . "Vous êtes mute, temps restant: §9" . $format);

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
            Faction::broadcastMessage($session->data["faction"], "§9[§fF§9] §fLe joueur de votre faction §9" . $player->getName() . " §fvient de se connecter");
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

            Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§9" . $player->getName() . " §fa rejoint le serveur pour la §9première §ffois ! Souhaitez lui la §9bienvenue §favec la commande §9/bvn §f(#§9" . $count . "§f)!");

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
            $entity = new LogoutNpc($player->getLocation(), $player->getSkin());
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
                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§9" . $player->getDisplayName() . "[§7" . $pot1 . "§9] §fa été tué par le joueur §9" . $damager->getDisplayName() . "[§7" . $pot2 . "§9]");

                $damagerSession = Session::get($damager);

                $damagerSession->addValue("kill", 1);
                $damagerSession->addValue("killstreak", 1);

                if (Faction::hasFaction($damager)) Faction::addPower($damagerSession->data["faction"], 6);
                if (Faction::hasFaction($player)) Faction::addPower($session->data["faction"], -4);

                $damagerKillstreak = $damagerSession->data["killstreak"];

                if ($playerBounty > 0) {
                    $damagerSession->addValue("money", $playerBounty);
                    Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§9" . $damager->getName() . " §fvient de remporter un prime de §9" . $playerBounty . " pièce(s) §fen tuant §9" . $player->getName() . " §f!");
                }

                if ($damagerKillstreak % 5 == 0) {
                    $amount = Cache::$config["bounties"][array_rand(Cache::$config["bounties"])];
                    $damagerSession->addValue("bounty", $amount);

                    Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§9" . $damager->getName() . " §fa fait §9" . $damagerSession->data["killstreak"] . " §fkills sans mourrir ! Sa mort est désormais mise à prix à §9" . Session::get($damager)->data["bounty"] . " pièce(s) §8(§7+" . $amount . "§8) §f!");
                }

                Jobs::addXp($damager, "Hunter", 50 + $damagerSession->data["killstreak"]);
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

    public function onItemDamage(ItemDamageEvent $event): void
    {
        ExtraVanillaItems::getItem($event->getItem())->onDamage($event);
    }

    public function onJump(PlayerJumpEvent $event): void
    {
        $player = $event->getPlayer();

        $x = $player->getPosition()->getFloorX();
        $y = $player->getPosition()->getFloorY() - 1;
        $z = $player->getPosition()->getFloorZ();

        $block = $player->getPosition()->getWorld()->getBlockAt($x, $y, $z);
        ExtraVanillaBlocks::getBlock($block)->onJump($event);
    }

    public function onSneak(PlayerToggleSneakEvent $event): void
    {
        $player = $event->getPlayer();

        $x = $player->getPosition()->getFloorX();
        $y = $player->getPosition()->getFloorY() - 1;
        $z = $player->getPosition()->getFloorZ();

        $block = $player->getPosition()->getWorld()->getBlockAt($x, $y, $z);
        ExtraVanillaBlocks::getBlock($block)->onSneak($event);
    }

    public function onDamage(EntityDamageEvent $event): void
    {
        $entity = $event->getEntity();

        if ($entity instanceof ItemEntity) {
            if ($event->getCause() === $event::CAUSE_CONTACT && $entity->getItem()->getTypeId() === VanillaBlocks::CACTUS()->asItem()->getTypeId()) {
                $entity->setMotion(new Vector3(0.3, 0.3, 0.3));
                $event->cancel();
            } else if ($event->getCause() === $event::CAUSE_VOID) {
                $entity->teleport($entity->getWorld()->getSpawnLocation());
                $event->cancel();
            }

            return;
        } else if ($event->getModifier(EntityDamageEvent::MODIFIER_PREVIOUS_DAMAGE_COOLDOWN) < 0.0) {
            $event->cancel();
            return;
        }

        if ($event instanceof EntityDamageByEntityEvent) {
            $damager = $event->getDamager();

            if ($damager instanceof Player && ExtraVanillaItems::getItem($damager->getInventory()->getItemInHand())->onAttack($event)) {
                return;
            }
        }

        if ($entity instanceof Living) {
            Armor::applyDamageModifiers($event, $entity);
        }

        if (!$entity instanceof Player) {
            return;
        }

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
                        "§r" . Util::PREFIX . "Sanction §9§l«" => "custom",
                        "§r" . Util::PREFIX . "Alias §9§l«" => "/alias \"" . $entity->getName() . "\"",
                        "§r" . Util::PREFIX . "Freeze §9§l«" => "/freeze \"" . $entity->getName() . "\"",
                        "§r" . Util::PREFIX . "Invsee §9§l«" => "/invsee \"" . $entity->getName() . "\"",
                        "§r" . Util::PREFIX . "Ecsee §9§l«" => "/ecsee \"" . $entity->getName() . "\"",
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
                            $damager->sendMessage("Vous venez de taper le joueur §9" . $entity->getName());
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

                PartnerItems::executeHitPartnerItem($damager, $entity);

                $damagerSession->setCooldown("combat", 20, [$entity->getName()]);
                $entitySession->setCooldown("combat", 20, [$damager->getName()]);

                $event->setKnockback(0.38);
                $event->setAttackCooldown(8);

                $damagerSession->data["last_hit"] = [$entity->getName(), time()];

                if ($entitySession->inCooldown("_focusmode") && $damager->getName() === $entitySession->getCooldownData("_focusmode")[1]) {
                    $event->setBaseDamage($event->getBaseDamage() + (($event->getBaseDamage() / 100) * 15));
                }

                Util::updateBounty($entity);
                Util::updateBounty($damager);
            }
        }

        skip:

        if (!$event->isCancelled() && in_array($entity->getName(), GamblingTask::$players) && $event->getFinalDamage() >= $entity->getHealth()) {
            $ev = new PlayerDeathEvent($entity, [], 0, "");
            $ev->call();

            $event->setBaseDamage(0);
        }
    }

    public function onExhaust(PlayerExhaustEvent $event): void
    {
        $event->getPlayer()->getHungerManager()->setExhaustion(2.5);
        $event->getPlayer()->getHungerManager()->setFood(18);
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
                "§r" . Util::PREFIX . "Vanish §9§l«" => "/vanish",
                "§r" . Util::PREFIX . "Random Tp §9§l«" => "/randomtp",
                "§r" . Util::PREFIX . "Spectateur §9§l«" => "/spec",
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

        ExtraVanillaItems::getItem($item)->onUse($event);
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
        $block = null;

        if (Session::get($player)->data["staff_mod"][0]) {
            $event->cancel();
            return;
        }

        foreach ($event->getTransaction()->getBlocks() as [$x, $y, $z, $transactionBlock]) {
            $block = $transactionBlock;

            if (!Faction::canBuild($player, $transactionBlock, "place")) {
                Util::antiBlockGlitch($player);

                $event->cancel();
                return;
            }
        }

        if ($block instanceof Block) {
            ExtraVanillaBlocks::getBlock($block)->onPlace($event);
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

    public function onDrop(PlayerDropItemEvent $event): void
    {
        if (in_array($event->getPlayer()->getName(), GamblingTask::$players)) {
            $event->cancel();
        }
    }

    public function onCraft(CraftItemEvent $event): void
    {
        $input = $event->getInputs();
        $player = $event->getPlayer();

        foreach ($input as $item) {
            if (!is_null($item->getNamedTag()->getTag("partneritem"))) {
                $event->cancel();
                Util::removeCurrentWindow($player);

                $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas utiliser des partneritems pour craft des items ou autre");
                break;
            } else if (!is_null($item->getNamedTag()->getTag("menu_item"))) {
                $event->cancel();
                Util::removeCurrentWindow($player);
                break;
            }
        }
    }

    public function onBreak(BlockBreakEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        $session = Session::get($player);

        if ($session->data["staff_mod"][0]) {
            $event->cancel();
            return;
        } else if (!$player->isCreative() && $player->getPosition()->getWorld()->getFolderName() === "mine" && $player->getPosition()->getFloorX() > 4500) {
            Util::addItems($player, $event->getDrops(), false);

            if ($session->data["money"] >= 15) {
                $session->addValue("money", 15, true);
                $player->sendTip("§9- 15 pièces");

                $event->setDrops([$block->asItem()->setCount(1)]);
                $event->setXpDropAmount(0);
            } else {
                $player->sendTip("§9Vous n'avez pas assez d'argent pour acheter les blocs (15 pièces/u)");
            }

            $event->cancel();
            return;
        } else if ($player->getPosition()->getWorld()->getFolderName() !== "mine" && !Faction::canBuild($player, $block, "break")) {
            if ($block->isFullCube()) {
                Util::antiBlockGlitch($player);
            }

            $event->cancel();
            return;
        }

        if ($session->data["cobblestone"] === false && ($block->hasSameTypeId(VanillaBlocks::COBBLESTONE()) || $block->hasSameTypeId(VanillaBlocks::STONE()))) {
            $event->setDrops([]);
        }

        if (!$player->isCreative() && $block->getPosition()->getWorld()->getFolderName() === "mine" && $player->getPosition()->getFloorX() < 4500) {
            $data = ExtraVanillaBlocks::getBlock($block)->getDropsMine($player, $block);

            if (is_array($data) && $data[0] > 0) {
                $item = $event->getItem();
                $position = $block->getPosition();

                if ($item instanceof Durable) {
                    $item->applyDamage(1);
                }

                $player->getInventory()->setItemInHand($item);
                $position->getWorld()->setBlock($position, $data[1], false);

                PlayerTask::$blocks[] = [time() + $data[0], $position, $data[2]];

                $position->getWorld()->addSound($position, new BlockBreakSound($block));
                $position->getWorld()->addParticle($position->add(0.5, 0.5, 0.5), new BlockBreakParticle($block));

                Util::addItems($player, $data[3]);

                if ($event->getXpDropAmount() > 0) {
                    $player->getXpManager()->addXp($event->getXpDropAmount());
                }
            }

            $event->cancel();
            return;
        }

        if (ExtraVanillaItems::getItem($event->getItem())->onBreak($event)) {
            var_dump("return1111");
            return;
        } else if (ExtraVanillaBlocks::getBlock($event->getBlock())->onBreak($event)) {
            var_dump("return");
            return;
        }

        if ($event->isCancelled()) {
            return;
        }

        if ($block->hasSameTypeId(VanillaBlocks::COBBLESTONE()) || $block->hasSameTypeId(VanillaBlocks::STONE())) {
            Jobs::addXp($player, "Mineur", 1);
        } else if ($block->hasSameTypeId(VanillaBlocks::MELON()) || ($block instanceof Crops && !$block->ticksRandomly())) {
            Jobs::addXp($player, "Farmeur", mt_rand(1, 3));
        }

        Util::addItems($player, $event->getDrops());

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

            if ($entity instanceof SwitchBall) {
                if (Session::get($damager)->inCooldown("teleportation_switch")) {
                    $damager->sendMessage(Util::PREFIX . "Vous ne pouvez pas vous téléporté puis switch un joueur");
                    return;
                }

                $player->teleport($damagerPos);
                $damager->teleport($playerPos);

                $player->broadcastSound(new EndermanTeleportSound());
                $player->broadcastSound(new EndermanTeleportSound());

                $damager->sendMessage(Util::PREFIX . "Vous avez été switch avec le joueur §9" . $player->getDisplayName());
                $player->sendMessage(Util::PREFIX . "Vous avez été switch avec le joueur §9" . $damager->getDisplayName());
            } else if ($entity instanceof AntiBackBall) {
                $player->setNoClientPredictions();

                $damager->sendMessage(Util::PREFIX . "Vous avez touché §9" . $player->getDisplayName() . " §favec votre antiback ball, il est donc freeze pendant §92 §fsecondes");
                $player->sendMessage(Util::PREFIX . "Vous avez été touché par une antiback ball par §9" . $damager->getDisplayName() . " §fvous êtes donc freeze pendant §92 §fsecondes");

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
            if ($entity instanceof LogoutNpc) {
                $name = $entity->player;
                $name = is_null($name) ? "" : $name;

                if (strtolower($username) === strtolower($name)) {
                    $entity->killed = true;
                    $entity->flagForDespawn();
                }
            }
        }
    }

    public function onConsume(PlayerItemConsumeEvent $event): void
    {
        $item = $event->getItem();

        if ($item->getTypeId() === VanillaItems::GOLDEN_APPLE()->getTypeId() || $item->getTypeId() === VanillaItems::GOLDEN_CARROT()->getTypeId()) {
            $event->cancel();
            return;
        }

        ExtraVanillaItems::getItem($item)->onConsume($event);
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