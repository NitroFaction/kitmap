<?php /** @noinspection PhpUnused */

namespace NCore\listener;

use NCore\Base;
use NCore\entity\entities\AntiBackBallEntity;
use NCore\entity\entities\SwitcherEntity;
use NCore\handler\Cache;
use NCore\handler\FactionAPI;
use NCore\handler\JobsAPI;
use NCore\handler\OtherAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\Fire;
use pocketmine\block\Lava;
use pocketmine\block\Liquid;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\entity\EntityTrampleFarmlandEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerBucketEvent;
use pocketmine\item\Durable;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\sound\BlockBreakSound;
use pocketmine\world\sound\EndermanTeleportSound;
use Util\item\items\custom\Pickaxe;

class WorldListener implements Listener
{
    public function onPlace(BlockPlaceEvent $event): void
    {
        $player = $event->getPlayer();

        if (!FactionAPI::canBuild($player, $event->getBlock(), "place")) {
            $event->cancel();
            Util::antiBlockGlitch($player);
        } else if (Session::get($player)->data["player"]["staff_mod"][0]) {
            $event->cancel();
        }
    }

    public function onUpdate(BlockUpdateEvent $event): void
    {
        if ($event->getBlock()->getId() === BlockLegacyIds::DIRT) {
            if ($event->getBlock()->getPosition()->getWorld() === Base::getInstance()->getServer()->getWorldManager()->getDefaultWorld()) {
                $event->cancel();
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
        }

        if ($source instanceof Lava && $sourcePos->getY() !== $blockPos->getY()) {
            $event->cancel();
        } else if ($source instanceof Liquid && $blockPos->getWorld() === Base::getInstance()->getServer()->getWorldManager()->getDefaultWorld()) {
            if (FactionAPI::inPlot($sourcePos->getX(), $sourcePos->getZ()) !== FactionAPI::inPlot($blockPos->getX(), $blockPos->getZ())) {
                $event->cancel();
            }
        }
    }

    public function onBucket(PlayerBucketEvent $event): void
    {
        $player = $event->getPlayer();

        if (!FactionAPI::canBuild($player, $event->getBlockClicked(), "place")) {
            $event->cancel();
        } else if (Session::get($player)->data["player"]["staff_mod"][0]) {
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
        $position = $block->getPosition();

        if (!is_null($event->getItem()->getNamedTag()->getTag("plothoe")) && array_key_exists("plot", $session->data["player"]) && $session->data["player"]["plot"][0]) {
            $x = $position->getX();
            $z = $position->getZ();

            if (!$player->isSneaking()) {
                $session->data["player"]["plot"][2] = $x . ":" . $z;
                $player->sendMessage(Util::PREFIX . "Deuxième position définis (§e" . $x . "§f, §e" . $z . "§f)");
            }

            $event->cancel();
        } else if ($player->getWorld()->getFolderName() === "farm" && $player->getGamemode() === GameMode::SURVIVAL()) {
            $event->cancel();

            if (in_array($block->getId(), [BlockLegacyIds::EMERALD_ORE, BlockLegacyIds::DIAMOND_ORE])) {
                $item = $player->getInventory()->getItemInHand();

                if ($item instanceof Pickaxe) {
                    $item->addBlockToCounter();
                } else if ($item instanceof Durable) {
                    $item->applyDamage(1);
                }

                $player->getInventory()->setItemInHand($item);
                $position->getWorld()->setBlock($position, BlockFactory::getInstance()->get(BlockLegacyIds::BEDROCK, 0));

                Base::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($position, $block) {
                    $position->getWorld()->setBlock($position, $block, false);
                }), 30 * 10);

                $position->getWorld()->addSound($position, new BlockBreakSound($block));
                $position->getWorld()->addParticle($position->add(0.5, 0.5, 0.5), new BlockBreakParticle($block));

                if ($block->getId() === BlockLegacyIds::EMERALD_ORE) {
                    foreach ($event->getDrops() as $item) {
                        OtherAPI::addItem($player, $item);
                    }

                    JobsAPI::addXp($player, "Mineur", 15);
                } else {
                    $rand = mt_rand(1, 50);

                    $session->addValue("money", $rand);
                    $player->sendTip(Util::PREFIX . "+ §e" . $rand . " §fPièces\n" . Util::PREFIX . "+ §e5 §fMineur");

                    JobsAPI::addXp($player, "Mineur", 5, false);
                }

                if ($event->getXpDropAmount() > 0) {
                    $player->getXpManager()->addXp($event->getXpDropAmount());
                }
            }
            return;
        }

        if (!FactionAPI::canBuild($player, $block, "break")) {
            $event->cancel();

            if ($block->isFullCube() || in_array($block->getId(), Cache::$config["cancel_block"])) {
                Util::antiBlockGlitch($player);
            }
        } else if ($session->data["player"]["staff_mod"][0]) {
            $event->cancel();
        } else if ($session->data["player"]["cobblestone"] === false && in_array($block->getId(), [BlockLegacyIds::COBBLESTONE, BlockLegacyIds::STONE])) {
            $event->setDrops([]);
        }

        if (!$event->isCancelled()) {
            foreach ($event->getDrops() as $item) {
                OtherAPI::addItem($player, $item);
            }

            if ($event->getXpDropAmount() > 0) {
                $player->getXpManager()->addXp($event->getXpDropAmount());
            }

            $event->setDrops([]);
            $event->setXpDropAmount(0);

            switch ($block->getId()) {
                case BlockLegacyIds::EMERALD_ORE:
                    JobsAPI::addXp($player, "Mineur", 15);
                    break;
                case BlockLegacyIds::STONE:
                case BlockLegacyIds::COBBLESTONE:
                    if ($block->getMeta() === 0) {
                        JobsAPI::addXp($player, "Mineur", 1);
                    }
                    break;
                case BlockLegacyIds::WHEAT_BLOCK:
                case BlockLegacyIds::CARROT_BLOCK:
                case BlockLegacyIds::BEETROOT_BLOCK:
                case BlockLegacyIds::POTATO_BLOCK:
                    if ($block->getMeta() === 7) {
                        JobsAPI::addXp($player, "Farmeur", mt_rand(1, 3));
                    }
                    break;
                case BlockLegacyIds::MELON_BLOCK:
                    JobsAPI::addXp($player, "Farmeur", mt_rand(1, 3));
                    break;
            }
        }
    }

    public function onHitByProjectile(ProjectileHitEntityEvent $event): void
    {
        $player = $event->getEntityHit();

        if ($player instanceof Player) {
            $entity = $event->getEntity();
            $damager = $entity->getOwningEntity();

            if ($damager instanceof Player) {
                $damagerPos = $damager->getPosition();
                $playerPos = $player->getPosition();

                if (OtherAPI::insideZone($damagerPos, "spawn") || OtherAPI::insideZone($playerPos, "spawn")) {
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
                    $player->setImmobile();

                    $damager->sendMessage(Util::PREFIX . "Vous avez touché §e" . $player->getDisplayName() . " §favec votre antiback ball, il est donc freeze pendant §e2 §fsecondes");
                    $player->sendMessage(Util::PREFIX . "Vous avez été touché par une antiback ball par §e" . $damager->getDisplayName() . " §fvous êtes donc freeze pendant §e2 §fsecondes");

                    Session::get($damager)->setCooldown("combat", 30, [$player->getName()]);
                    Session::get($player)->setCooldown("combat", 30, [$damager->getName()]);

                    Base::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player) {
                        if ($player->isOnline()) {
                            $player->setImmobile(false);
                        }
                    }), 2 * 20);
                }
            }
        }
    }
}