<?php /** @noinspection PhpUnused */

namespace NCore\listener;

use NCore\Base;
use NCore\command\player\util\Bienvenue;
use NCore\command\player\util\faction\Anvil;
use NCore\command\player\util\faction\Enchant;
use NCore\command\staff\event\Question;
use NCore\command\staff\sanction\Ban;
use NCore\command\staff\tool\LastInventory;
use NCore\command\staff\tool\Vanish;
use NCore\command\sub\faction\admin\Plots;
use NCore\entity\entities\LogoutEntity;
use NCore\entity\EntityManager;
use NCore\handler\Cache;
use NCore\handler\FactionAPI;
use NCore\handler\JobsAPI;
use NCore\handler\OtherAPI;
use NCore\handler\PackAPI;
use NCore\handler\PartnerItemsAPI;
use NCore\handler\RankAPI;
use NCore\handler\SanctionAPI;
use NCore\handler\SkinAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\entity\object\ItemEntity;
use pocketmine\event\entity\EntityCombustEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChangeSkinEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDataSaveEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerJumpEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use skymin\bossbar\BossBarAPI;
use Util\entity\ai\PassiveAI;
use Util\entity\entities\Boss;
use Util\item\items\IrisGlove;
use Util\util\IdsUtils;
use Webmozart\PathUtil\Path;

class PlayerListener implements Listener
{
    public function onInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        $session = Session::get($player);
        $item = $event->getItem();

        if (!is_null($item->getNamedTag()->getTag("plothoe")) && array_key_exists("plot", $session->data["player"]) && $session->data["player"]["plot"][0]) {
            $x = $block->getPosition()->getX();
            $z = $block->getPosition()->getZ();

            if ($player->isSneaking()) {
                Plots::confirm($player);
            } else if ($event->getAction() === $event::LEFT_CLICK_BLOCK) {
                $session->data["player"]["plot"][2] = $x . ":" . $z;
                $player->sendMessage(Util::PREFIX . "Deuxième position définis (§e" . $x . "§f, §e" . $z . "§f)");
            } else {
                $session->data["player"]["plot"][1] = $x . ":" . $z;
                $player->sendMessage(Util::PREFIX . "Première position définis (§e" . $x . "§f, §e" . $z . "§f)");
            }

            $event->cancel();
        } else if (!FactionAPI::canBuild($player, $block, "interact") && (in_array($block->getId(), Cache::$config["cancel_block"]) || in_array($item->getId(), Cache::$config["item_cancel"]))) {
            $event->cancel();

            if ($event->getAction() === $event::RIGHT_CLICK_BLOCK && in_array($block->getId(), Cache::$config["cancel_block"])) {
                Util::antiBlockGlitch($player);
            }
        } else if (!$player->isSneaking() && $event->getAction() === $event::RIGHT_CLICK_BLOCK && $block->getId() === BlockLegacyIds::ANVIL) {
            $event->cancel();
            $player->removeCurrentWindow();

            Anvil::openAnvil($player);
        } else if (!$player->isSneaking() && $event->getAction() === $event::RIGHT_CLICK_BLOCK && $block->getId() === BlockLegacyIds::ENCHANTING_TABLE) {
            $event->cancel();
            $player->removeCurrentWindow();

            Enchant::openEnchantTable($player, false);
        } else if (
            ($block->getPosition()->getX() === 23 && $block->getPosition()->getY() === 62 && $block->getPosition()->getZ() === 39) ||
            ($block->getPosition()->getX() === 18 && $block->getPosition()->getY() === 62 && $block->getPosition()->getZ() === 44)
        ) {
            $event->cancel();
            $player->removeCurrentWindow();

            PackAPI::openPackUI($player);
        } else if ($item instanceof IrisGlove) {
            if (!str_starts_with($player->getWorld()->getFolderName(), "island-")) {
                $player->sendMessage(Util::PREFIX . "Le gant en iris ne marche que sur les îles");
                return;
            }

            $item->onInteract($player, $block);
        }
    }

    public function onJump(PlayerJumpEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $player->getWorld()->getBlock($player->getPosition()->subtract(0, 1, 0));

        if ($block->getId() !== IdsUtils::ELEVATOR_BLOCK) {
            return;
        } else if (!OtherAPI::getTwoBlocksAvaible($block)) {
            $player->sendMessage(Util::PREFIX . "L'elevateur ou vous êtes est inutilisable");
            return;
        }

        $x = $player->getPosition()->getFloorX();
        $y = $player->getPosition()->getY();
        $z = $player->getPosition()->getFloorZ();

        $maxY = $player->getWorld()::Y_MAX;
        $found = false;
        $y++;

        for (; $y <= $maxY; $y++) {
            if (($found = OtherAPI::isElevatorBlock($x, $y, $z, $player->getWorld())) instanceof Block) {
                break;
            }
        }

        if ($found instanceof Block) {
            if (!OtherAPI::getTwoBlocksAvaible($found)) {
                $player->sendMessage(Util::PREFIX . "L'elevateur au dessus est inutilisable");
                return;
            }

            $player->teleport(new Vector3($x + 0.5, $y + 1, $z + 0.5));
        } else {
            $player->sendMessage(Util::PREFIX . "Aucun elevateur au dessus");
        }
    }

    public function onSneak(PlayerToggleSneakEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $player->getWorld()->getBlock($player->getPosition()->subtract(0, 1, 0));

        if (!$player->isSneaking() || $block->getId() !== IdsUtils::ELEVATOR_BLOCK) {
            return;
        } else if (!OtherAPI::getTwoBlocksAvaible($block)) {
            $player->sendMessage(Util::PREFIX . "L'elevateur ou vous êtes est inutilisable");
            return;
        }

        $x = $player->getPosition()->getFloorX();
        $y = $player->getPosition()->getY() - 2;
        $z = $player->getPosition()->getFloorZ();

        $found = false;
        $y--;

        for (; $y >= 0; $y--) {
            if (($found = OtherAPI::isElevatorBlock($x, $y, $z, $player->getWorld())) instanceof Block) {
                break;
            }
        }

        if ($found instanceof Block) {
            if (!OtherAPI::getTwoBlocksAvaible($found)) {
                $player->sendMessage(Util::PREFIX . "L'elevateur au dessus est inutilisable");
                return;
            }

            $player->teleport(new Vector3($x + 0.5, $y + 1, $z + 0.5));
        } else {
            $player->sendMessage(Util::PREFIX . "Aucun elevateur en dessous");
        }
    }

    public function onCombust(EntityCombustEvent $event): void
    {
        $entity = $event->getEntity();

        if ($entity instanceof Player) {
            if (Session::get($entity)->data["player"]["staff_mod"][0]) {
                $event->cancel();
            }
        }
    }

    public function onChat(PlayerChatEvent $event): void
    {
        $player = $event->getPlayer();
        $message = $event->getMessage();

        $session = Session::get($player);

        if (Question::$currentEvent !== 0) {
            $valid = false;

            switch (Question::$currentEvent) {
                case 1:
                    if ($event->getMessage() === Question::$currentReply) {
                        Base::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§e" . $player->getDisplayName() . " §fa gagné §e5k$ §fen ayant réécrit le code §e" . Question::$currentReply . " §fen premier !");
                        $valid = true;
                    }
                    break;
                case 2:
                    if (strtolower($event->getMessage()) === Question::$currentReply) {
                        Base::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§e" . $player->getDisplayName() . " §fa gagné §e5k$ §fen ayant trouver le mot §e" . Question::$currentReply . " §fen premier !");
                        $valid = true;
                    }
                    break;
                case 3:
                    if ($event->getMessage() === strval(Question::$currentReply)) {
                        Base::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§e" . $player->getDisplayName() . " §fa gagné §e5k$ §fen ayant répondu au calcul §e" . Question::$currentReply . " §fen premier !");
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
            if (!$player->hasPermission("pocketmine.group.operator")) {
                $session->setCooldown("chat", 2);
            }
        }

        if (($session->data["player"]["faction_chat"] || $event->getMessage()[0] === "-") && FactionAPI::hasFaction($player)) {
            if (!$session->data["player"]["faction_chat"]) {
                $message = substr($message, 1);
            }

            $faction = $session->data["player"]["faction"];
            $event->cancel();

            Base::getInstance()->getLogger()->info("[F] [" . $faction . "] " . $player->getName() . " » " . $message);
            FactionAPI::broadcastMessage($faction, "§e[§fF§e] §f" . $player->getName() . " " . Util::PREFIX . $message);
        } else if (($session->data["player"]["staff_chat"] || $event->getMessage()[0] === "!") && $player->hasPermission("staff.group")) {
            if (!$session->data["player"]["staff_chat"]) {
                $message = substr($message, 1);
            }

            $event->cancel();

            foreach (Base::getInstance()->getServer()->getOnlinePlayers() as $target) {
                if ($target->hasPermission("staff.group")) {
                    $target->sendMessage("§f[§eS§f] [§eStaffChat§f] §e" . $player->getName() . " " . Util::PREFIX . $message);
                }
            }

            Base::getInstance()->getLogger()->info("[S] [StaffChat] " . $player->getName() . " » " . $message);
        } else if ($session->inCooldown("mute")) {
            $player->sendMessage(Util::PREFIX . "Vous êtes mute, temps restant: §e" . SanctionAPI::format($session->getCooldownData("mute")[0] - time()));
            $event->cancel();
        } else if (!$player->hasPermission("pocketmine.group.operator") && str_contains($message, "@here")) {
            $player->sendMessage(Util::PREFIX . "Votre message ne peut pas contenir §e\"@here\"");
            $event->cancel();
        }

        if (!$event->isCancelled()) {
            if (!RankApi::hasRank($player, "roi")) {
                $message = TextFormat::clean($message);
            }

            if ($message === "") {
                $event->cancel();
                return;
            }

            $rank = ($player->getName() === $player->getDisplayName()) ? RankAPI::getRank($player->getName()) : "joueur";
            $event->setFormat(RankAPI::setReplace(RankAPI::getRankValue($rank, "chat"), $player, $message));
        }
    }

    public function onChangeSkin(PlayerChangeSkinEvent $event): void
    {
        $skin = SkinAPI::checkSkin($event->getPlayer(), $event->getNewSkin());
        $event->setNewSkin($skin);
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

        Base::getInstance()->getServer()->broadcastTip("§a+ " . $player->getName() . " +");

        if (FactionAPI::hasFaction($player)) {
            Cache::$factions[$session->data["player"]["faction"]]["activity"][date("d-m")] = $player->getName();
            FactionAPI::broadcastMessage($session->data["player"]["faction"], "§e[§fF§e] §fLe joueur de votre faction §e" . $player->getName() . " §fvient de se connecter");
        }

        foreach (Vanish::$vanish as $target) {
            $target = Base::getInstance()->getServer()->getPlayerByPrefix($target);

            if ($target instanceof Player) {
                if ($target->hasPermission("staff.group") || $target->getName() === $player->getName()) {
                    continue;
                }
                $target->hidePlayer($player);
            }
        }

        if (!$player->hasPlayedBefore()) {
            $path = Path::join(Base::getInstance()->getServer()->getDataPath(), "players");
            $count = count(glob($path . "/*")) + 1;

            Base::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§e" . $player->getName() . " §fa rejoint le serveur pour la §epremière §ffois ! Souhaitez lui la §ebienvenue §favec la commande §e/bvn §f(#§e" . $count . "§f)!");

            Bienvenue::$alreadyWished = [];
            Bienvenue::$lastJoin = $player->getName();
        }

        if ($session->data["player"]["staff_mod"][0] && $player->getGamemode() === GameMode::SURVIVAL()) {
            $player->setAllowFlight(true);
        }

        if ($player->getGamemode() === GameMode::ADVENTURE()) {
            $player->setGamemode(GameMode::SURVIVAL());
        }

        $pk = new GameRulesChangedPacket();
        $pk->gameRules = ["showcoordinates" => new BoolGameRule($session->data["player"]["xyz"], false)];
        $player->getNetworkSession()->sendDataPacket($pk);

        RankAPI::updateNameTag($player);
        RankAPI::addPermissions($player);

        SkinAPI::checkSkin($player);
    }

    public function onQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();

        Base::getInstance()->getServer()->broadcastTip("§c- " . $player->getName() . " -");
        $event->setQuitMessage("");

        if (OtherAPI::getTpTime($player) > 0) {
            $entity = new LogoutEntity($player->getLocation(), $player->getSkin());
            $entity->initEntityB($player);
            $entity->spawnToAll();
        }

        Session::get($player)->saveSessionData();
    }

    public function onDeath(EntityDeathEvent $event): void
    {
        $entity = $event->getEntity();

        if (!$entity instanceof Player) {
            if ($entity instanceof Boss) {
                foreach (Base::getInstance()->getServer()->getOnlinePlayers() as $player) {
                    BossBarAPI::getInstance()->hideBossBar($player);
                }

                EntityManager::dropItems($entity->getPosition(), 40);
            }
            return;
        } else if (!$event instanceof PlayerDeathEvent) {
            return;
        }

        $player = $entity;
        $session = Session::get($player);

        $event->setDeathMessage("");

        $session->removeCooldown("combat");
        $session->addValue("death", 1);

        $killstreak = $session->data["player"]["killstreak"];
        $session->data["player"]["killstreak"] = 0;

        if (RankAPI::hasRank($player, "elite")) {
            $session->data["player"]["respawn_xp"] = $player->getXpManager()->getCurrentTotalXp();
            $event->setXpDropAmount(0);
        }

        $cause = $player->getLastDamageCause();

        if (!is_null($cause) && $cause->getCause() === EntityDamageEvent::CAUSE_ENTITY_ATTACK) {
            /** @noinspection PhpPossiblePolymorphicInvocationInspection */
            $damager = $cause->getDamager();

            if ($cause instanceof EntityDamageByEntityEvent && $damager instanceof Player) {
                LastInventory::saveOnlineInventory($player, $damager, $killstreak);
                $damagerSession = Session::get($damager);

                $pot1 = OtherAPI::getItemCount($player, ItemIds::SPLASH_POTION, 22);
                $pot2 = OtherAPI::getItemCount($damager, ItemIds::SPLASH_POTION, 22);

                Base::getInstance()->getLogger()->info($player->getDisplayName() . " (" . $player->getName() . ") a été tué par " . $damager->getDisplayName() . " (" . $damager->getName() . ")");
                Base::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§e" . $player->getDisplayName() . "[§7" . $pot1 . "§e] §fa été tué par le joueur §e" . $damager->getDisplayName() . "[§7" . $pot2 . "§e]");

                $damagerSession->addValue("kill", 1);
                $damagerSession->addValue("killstreak", 1);

                if (FactionAPI::hasFaction($damager)) FactionAPI::addPower($damagerSession->data["player"]["faction"], 6);
                if (FactionAPI::hasFaction($player)) FactionAPI::addPower($session->data["player"]["faction"], -4);

                if ($damagerSession->data["player"]["killstreak"] % 5 == 0) {
                    Base::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le joueur §e" . $damager->getName() . " §fa fait §e" . $damagerSession->data["player"]["killstreak"] . " §fkill sans mourrir !");
                }

                JobsAPI::addXp($damager, "Hunter", 50 + $damagerSession->data["player"]["killstreak"]);
                return;
            }
        } else {
            Base::getInstance()->getLogger()->info($player->getDisplayName() . " (" . $player->getName() . ") est mort");
        }

        LastInventory::saveOnlineInventory($player, null, $killstreak);
    }

    public function onCommand(CommandEvent $event): void
    {
        $sender = $event->getSender();

        $command = explode(" ", $event->getCommand());
        Base::getInstance()->getLogger()->info("[" . $sender->getName() . "] " . implode(" ", $command));

        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->inCooldown("cmd")) {
                $event->cancel();
            } else {
                if (!$sender->hasPermission("pocketmine.group.operator")) {
                    $session->setCooldown("cmd", 1);
                }
            }

            if ($sender->isImmobile()) {
                $event->cancel();
                return;
            }

            $command[0] = strtolower($command[0]);
            $event->setCommand(implode(" ", $command));
        }
    }

    public function onDataPacketReceive(DataPacketReceiveEvent $event): void
    {
        $packet = $event->getPacket();
        $session = $event->getOrigin();

        $player = $session->getPlayer();

        if ($player instanceof Player) {
            if ($packet instanceof AnimatePacket && $packet->action === AnimatePacket::ACTION_SWING_ARM) {
                $event->cancel();
                $player->getServer()->broadcastPackets($player->getViewers(), [$packet]);
            }
        }
    }

    public function onRespawn(PlayerRespawnEvent $event): void
    {
        $player = $event->getPlayer();
        $session = Session::get($player);

        if (isset($session->data["player"]["respawn_xp"])) {
            $player->getXpManager()->setCurrentTotalXp($session->data["player"]["respawn_xp"]);
            unset($session->data["player"]["respawn_xp"]);
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

        foreach (Base::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getEntities() as $entity) {
            if ($entity instanceof LogoutEntity) {
                $name = $entity->player;

                if (strtolower($username) === strtolower($name)) {
                    $entity->killed = true;
                    $entity->flagForDespawn();
                }
            }
        }
    }

    /**
     * @priority HIGH
     */
    public function onDamage(EntityDamageEvent $event): void
    {
        $entity = $event->getEntity();

        if ($entity instanceof ItemEntity) {
            if ($event->getCause() === EntityDamageEvent::CAUSE_VOID) {
                $world = $event->getEntity()->getPosition()->getWorld();

                if (str_starts_with($world->getFolderName(), "island-")) {
                    $event->cancel();
                    $entity->teleport($world->getSpawnLocation());
                    return;
                }
            }
        }

        if (!$event->isCancelled() && $event->getModifier(EntityDamageEvent::MODIFIER_PREVIOUS_DAMAGE_COOLDOWN) < 0.0) {
            $event->cancel();
        }

        if ($entity instanceof Player) {
            $entitySession = Session::get($entity);

            if ($event->getCause() === EntityDamageEvent::CAUSE_VOID) {
                $entity->teleport($entity->getWorld()->getSpawnLocation());
                $event->cancel();
            } else if ($event->getCause() === EntityDamageEvent::CAUSE_FALL || $entity->getWorld()->getFolderName() === "farm" || str_starts_with($entity->getWorld()->getFolderName(), "island-") || OtherAPI::insideZone($entity->getPosition(), "spawn") || $entitySession->data["player"]["staff_mod"][0]) {
                $event->cancel();
            }

            if ($event instanceof EntityDamageByEntityEvent) {
                $damager = $event->getDamager();

                if ($entity->getWorld()->getFolderName() === "farm" || str_starts_with($entity->getWorld()->getFolderName(), "island-")) {
                    if ($damager instanceof PassiveAI) {
                        $event->uncancel();
                    }
                }

                if (!is_null($damager) && OtherAPI::insideZone($damager->getPosition(), "spawn")) {
                    $event->cancel();
                } else if (!$damager instanceof Player) {
                    return;
                }
                $damagerSession = Session::get($damager);

                if ($damagerSession->data["player"]["staff_mod"][0]) {
                    switch ($damager->getInventory()->getItemInHand()->getCustomName()) {
                        case "§r" . Util::PREFIX . "Sanction §e§l«":
                            SanctionAPI::chooseSanction($damager, strtolower($entity->getName()));
                            break;
                        case "§r" . Util::PREFIX . "Alias §e§l«":
                            $damager->chat("/alias \"" . $entity->getName() . "\"");
                            break;
                        case "§r" . Util::PREFIX . "Freeze §e§l«":
                            $damager->chat("/freeze \"" . $entity->getName() . "\"");
                            break;
                        case "§r" . Util::PREFIX . "Invsee §e§l«":
                            $damager->chat("/invsee \"" . $entity->getName() . "\"");
                            break;
                        case "§r" . Util::PREFIX . "Ecsee §e§l«":
                            $damager->chat("/ecsee \"" . $entity->getName() . "\"");
                            break;
                        case "§r" . Util::PREFIX . "Knockback 2 §e§l«":
                            return;
                        default:
                            $damager->sendMessage(Util::PREFIX . "Vous venez de taper le joueur §e" . $entity->getName());
                            break;
                    }

                    $event->cancel();
                }

                if (FactionAPI::hasFaction($damager) && FactionAPI::hasFaction($entity) && $damagerSession->data["player"]["faction"] === $entitySession->data["player"]["faction"]) {
                    $event->cancel();
                }
                if ($event->isCancelled() || $entity->getGamemode() === GameMode::CREATIVE() || $damager->getGamemode() === GameMode::CREATIVE() || $entity->isImmobile() || $entity->isFlying() || $entity->getAllowFlight()) {
                    return;
                }

                PartnerItemsAPI::executeHitPartnerItem($damager, $entity);

                $damagerSession->setCooldown("combat", 30, [$entity->getName()]);
                $entitySession->setCooldown("combat", 30, [$damager->getName()]);

                $event->setKnockback(0.38);
                $event->setAttackCooldown(8.60);

                $damagerSession->data["player"]["last_hit"] = [$entity->getName(), time()];

                if ($entitySession->inCooldown("_focusmode") && $damager->getName() === $entitySession->getCooldownData("_focusmode")[1]) {
                    $event->setBaseDamage($event->getBaseDamage() + (($event->getBaseDamage() / 100) * 15));
                }

                $entity->setScoreTag("§7" . round($entity->getHealth(), 2) . " §c❤");
            }
        }
    }
}