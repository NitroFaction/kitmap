<?php /** @noinspection PhpUnused */

namespace Kitmap\listener;

use Kitmap\command\player\{Anvil, Enchant, rank\Enderchest};
use Kitmap\command\staff\{Ban, LastInventory, Question, Vanish};
use Kitmap\command\util\Bienvenue;
use Kitmap\entity\{AntiBackBallEntity, LogoutEntity, SwitcherEntity};
use Kitmap\handler\{Cache, Faction, Pack, PartnerItems, Rank, Sanction};
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\block\{Fire, inventory\EnderChestInventory, Lava, Liquid, VanillaBlocks};
use pocketmine\entity\effect\{EffectInstance, VanillaEffects};
use pocketmine\event\block\{BlockBreakEvent, BlockPlaceEvent, BlockSpreadEvent};
use pocketmine\event\entity\{EntityDamageByEntityEvent,
    EntityDamageEvent,
    EntityItemPickupEvent,
    EntityShootBowEvent,
    EntityTrampleFarmlandEvent,
    ItemSpawnEvent,
    ProjectileHitEntityEvent};
use pocketmine\event\inventory\{CraftItemEvent, InventoryOpenEvent, InventoryTransactionEvent};
use pocketmine\event\Listener;
use pocketmine\event\player\{PlayerBucketEvent,
    PlayerChatEvent,
    PlayerDataSaveEvent,
    PlayerDeathEvent,
    PlayerInteractEvent,
    PlayerItemConsumeEvent,
    PlayerItemUseEvent,
    PlayerJoinEvent,
    PlayerPreLoginEvent,
    PlayerQuitEvent,
    PlayerRespawnEvent};
use pocketmine\event\server\CommandEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\{EnderPearl, PotionType, VanillaItems};
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\{GameMode, Player};
use pocketmine\player\chat\LegacyRawChatFormatter;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\EndermanTeleportSound;
use Symfony\Component\Filesystem\Path;

class PlayerListener implements Listener
{
    public function onInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        if (!Faction::canBuild($player, $block, "interact")) {
            $event->cancel();

            if ($event->getAction() === $event::RIGHT_CLICK_BLOCK) {
                Util::antiBlockGlitch($player);
            }
        } else if (!$player->isSneaking() && $event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK && $block->isSameState(VanillaBlocks::ANVIL())) {
            $event->cancel();
            $player->removeCurrentWindow();

            Anvil::openAnvil($player);
        } else if (!$player->isSneaking() && $event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK && $block->isSameState(VanillaBlocks::ENCHANTING_TABLE())) {
            $event->cancel();
            $player->removeCurrentWindow();

            Enchant::openEnchantTable($player, false);
        } else if ($block->getPosition()->getX() === -1 && $block->getPosition()->getY() === 64 && $block->getPosition()->getZ() === -56) {
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

        if (Question::$currentEvent !== 0) {
            $valid = false;

            switch (Question::$currentEvent) {
                case 1:
                    if ($event->getMessage() === Question::$currentReply) {
                        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§e" . $player->getDisplayName() . " §fa gagné §e5k$ §fen ayant réécrit le code §e" . Question::$currentReply . " §fen premier !");
                        $valid = true;
                    }
                    break;
                case 2:
                    if (strtolower($event->getMessage()) === Question::$currentReply) {
                        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§e" . $player->getDisplayName() . " §fa gagné §e5k$ §fen ayant trouver le mot §e" . Question::$currentReply . " §fen premier !");
                        $valid = true;
                    }
                    break;
                case 3:
                    if ($event->getMessage() === strval(Question::$currentReply)) {
                        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§e" . $player->getDisplayName() . " §fa gagné §e5k$ §fen ayant répondu au calcul §e" . Question::$currentReply . " §fen premier !");
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
            Faction::broadcastMessage($faction, "§e[§fF§e] §f" . $player->getName() . " " . Util::PREFIX . $message);

            return;
        } else if ($session->inCooldown("mute")) {
            $format = Util::formatDurationFromSeconds($session->getCooldownData("mute")[0] - time());
            $player->sendMessage(Util::PREFIX . "Vous êtes mute, temps restant: §e" . $format);

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
            Cache::$factions[$session->data["faction"]]["activity"][date("d-m")] = $player->getName();
            Faction::broadcastMessage($session->data["faction"], "§e[§fF§e] §fLe joueur de votre faction §e" . $player->getName() . " §fvient de se connecter");
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

            Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§e" . $player->getName() . " §fa rejoint le serveur pour la §epremière §ffois ! Souhaitez lui la §ebienvenue §favec la commande §e/bvn §f(#§e" . $count . "§f)!");

            Bienvenue::$alreadyWished = [];
            Bienvenue::$lastJoin = $player->getName();
        }

        if ($session->data["staff_mod"][0] && $player->getGamemode() === GameMode::SURVIVAL()) {
            $player->setAllowFlight(true);
        }

        Util::givePlayerPreferences($player);

        Rank::updateNameTag($player);
        Rank::addPermissions($player);
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
                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§e" . $player->getDisplayName() . "[§7" . $pot1 . "§e] §fa été tué par le joueur §e" . $damager->getDisplayName() . "[§7" . $pot2 . "§e]");

                $damagerSession = Session::get($damager);

                $damagerSession->addValue("kill", 1);
                $damagerSession->addValue("killstreak", 1);

                if (Faction::hasFaction($damager)) Faction::addPower($damagerSession->data["faction"], 6);
                if (Faction::hasFaction($player)) Faction::addPower($session->data["faction"], -4);

                if ($damagerSession->data["killstreak"] % 5 == 0) {
                    Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le joueur §e" . $damager->getName() . " §fa fait §e" . $damagerSession->data["killstreak"] . " §fkill sans mourrir !");
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
                "§r" . Util::PREFIX . "Vanish §e§l«" => "/vanish",
                "§r" . Util::PREFIX . "Random Tp §e§l«" => "/randomtp",
                "§r" . Util::PREFIX . "Spectateur §e§l«" => "/spec",
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
                $player->sendMessage(Util::PREFIX . "Veuillez attendre §e" . ($session->getCooldownData("enderpearl")[0] - time()) . " §fsecondes avant de relancer une nouvelle perle");
                $event->cancel();
            } else {
                if ($session->inCooldown("_antipearl")) {
                    $player->sendTip(Util::PREFIX . "Veuillez attendre §e" . ($session->getCooldownData("_antipearl")[0] - time()) . " §fsecondes avant de relancer une nouvelle perle");
                    $event->cancel();
                    return;
                } else if (!is_null($item->getNamedTag()->getTag("partneritem"))) {
                    $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas utiliser cette perle");
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
                $player->sendMessage(Util::PREFIX . "Veuillez attendre §e" . ($session->getCooldownData("cookie_combined")[0] - time()) . " §fsecondes avant de remanger un cookie combiné");
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
                $player->sendMessage(Util::PREFIX . "Veuillez attendre §e" . ($session->getCooldownData("cookie_regeneration")[0] - time()) . " §fsecondes avant de remanger un cookie de regeneration");
                $event->cancel();
            } else {
                $player->getEffects()->add(new EffectInstance(VanillaEffects::REGENERATION(), (10 * 20), 0, false));
                $session->setCooldown("cookie_regeneration", 25);
            }
        } else if ($item->equals(VanillaItems::RAW_SALMON())) {
            if ($session->inCooldown("cookie_speed")) {
                $player->sendMessage(Util::PREFIX . "Veuillez attendre §e" . ($session->getCooldownData("cookie_speed")[0] - time()) . " §fsecondes avant de remanger un cookie de vitesse");
                $event->cancel();
            } else {
                $player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), (240 * 20), 0, false));
                $session->setCooldown("cookie_speed", 25);
            }
        } else if ($item->equals(VanillaItems::COOKED_SALMON())) {
            if ($session->inCooldown("cookie_strength")) {
                $player->sendMessage(Util::PREFIX . "Veuillez attendre §e" . ($session->getCooldownData("cookie_strength")[0] - time()) . " §fsecondes avant de remanger un cookie de force");
                $event->cancel();
            } else {
                $player->getEffects()->add(new EffectInstance(VanillaEffects::STRENGTH(), (240 * 20), 0, false));
                $session->setCooldown("cookie_strength", 25);
            }
        } else if ($item->equals(VanillaItems::GOLDEN_APPLE())) {
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

    public function onTrampleFarmland(EntityTrampleFarmlandEvent $event): void
    {
        $event->cancel();
    }

    public function onBreak(BlockBreakEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        $session = Session::get($player);

        if (!Faction::canBuild($player, $block, "break")) {
            if ($block->isFullCube()) {
                Util::antiBlockGlitch($player);
            }

            $event->cancel();
            return;
        } else if ($session->data["staff_mod"][0]) {
            $event->cancel();
            return;
        } else if ($session->data["cobblestone"] === false && ($block->isSameState(VanillaBlocks::COBBLESTONE()) || $block->isSameState(VanillaBlocks::STONE()))) {
            $event->setDrops([]);
        }

        foreach ($event->getDrops() as $item) {
            Util::addItem($player, $item);
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

                $damager->sendMessage(Util::PREFIX . "Vous avez été switch avec le joueur §e" . $player->getDisplayName());
                $player->sendMessage(Util::PREFIX . "Vous avez été switch avec le joueur §e" . $damager->getDisplayName());
            } else if ($entity instanceof AntiBackBallEntity) {
                $player->setNoClientPredictions();

                $damager->sendMessage(Util::PREFIX . "Vous avez touché §e" . $player->getDisplayName() . " §favec votre antiback ball, il est donc freeze pendant §e2 §fsecondes");
                $player->sendMessage(Util::PREFIX . "Vous avez été touché par une antiback ball par §e" . $damager->getDisplayName() . " §fvous êtes donc freeze pendant §e2 §fsecondes");

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

            if (
                $event->getCause() === EntityDamageEvent::CAUSE_FALL ||
                Util::insideZone($entity->getPosition(), "spawn") ||
                $entitySession->data["staff_mod"][0]
            ) {
                $event->cancel();
                return;
            }

            if ($event instanceof EntityDamageByEntityEvent && ($damager = $event->getDamager()) instanceof Player) {
                if (Util::insideZone($damager->getPosition(), "spawn")) {
                    $event->cancel();
                    return;
                }

                $damagerSession = Session::get($damager);
                if ($damagerSession->data["staff_mod"][0]) {
                    $message = match ($damager->getInventory()->getItemInHand()->getCustomName()) {
                        "§r" . Util::PREFIX . "Sanction §e§l«" => "custom",
                        "§r" . Util::PREFIX . "Alias §e§l«" => "/alias \"" . $entity->getName() . "\"",
                        "§r" . Util::PREFIX . "Freeze §e§l«" => "/freeze \"" . $entity->getName() . "\"",
                        "§r" . Util::PREFIX . "Invsee §e§l«" => "/invsee \"" . $entity->getName() . "\"",
                        "§r" . Util::PREFIX . "Ecsee §e§l«" => "/ecsee \"" . $entity->getName() . "\"",
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
                            $damager->sendMessage("Vous venez de taper le joueur §e" . $entity->getName());
                        }
                    }

                    $event->cancel();
                    return;
                }

                if (Faction::hasFaction($damager) && Faction::hasFaction($entity) && $damagerSession->data["faction"] === $entitySession->data["faction"]) {
                    $event->cancel();
                    return;
                }
                if ($entity->getGamemode() === GameMode::CREATIVE() || $damager->getGamemode() === GameMode::CREATIVE() || $entity->hasNoClientPredictions()) {
                    return;
                }

                PartnerItems::executeHitPartnerItem($damager, $entity);

                $damagerSession->setCooldown("combat", 30, [$entity->getName()]);
                $entitySession->setCooldown("combat", 30, [$damager->getName()]);

                $event->setKnockback(0.38);
                $event->setAttackCooldown(8.60);

                $damagerSession->data["last_hit"] = [$entity->getName(), time()];

                if ($entitySession->inCooldown("_focusmode") && $damager->getName() === $entitySession->getCooldownData("_focusmode")[1]) {
                    $event->setBaseDamage($event->getBaseDamage() + (($event->getBaseDamage() / 100) * 15));
                }

                $entity->setScoreTag("§7" . round($entity->getHealth(), 2) . " §c❤");
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
}