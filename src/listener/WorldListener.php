<?php /** @noinspection PhpUnused */

namespace Kitmap\listener;

use Kitmap\entity\entities\AntiBackBallEntity;
use Kitmap\entity\entities\SwitcherEntity;
use Kitmap\handler\Cache;
use Kitmap\handler\Faction;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
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
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\sound\EndermanTeleportSound;
use Util\item\items\custom\Pickaxe;

class WorldListener implements Listener
{
    public function onPlace(BlockPlaceEvent $event): void
    {
        $player = $event->getPlayer();

        if (!Faction::canBuild($player, $event->getBlock(), "place")) {
            $event->cancel();
            Util::antiBlockGlitch($player);
        } elseif (Session::get($player)->data["staff_mod"][0]) {
            $event->cancel();
        }
    }

    public function onUpdate(BlockUpdateEvent $event): void
    {
        if ($event->getBlock()->getId() === BlockLegacyIds::DIRT) {
            if ($event->getBlock()->getPosition()->getWorld() === Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()) {
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
        } elseif ($source instanceof Liquid && $blockPos->getWorld() === Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()) {
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
        } elseif (Session::get($player)->data["staff_mod"][0]) {
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

        if (!Faction::canBuild($player, $block, "break")) {
            $event->cancel();

            if ($block->isFullCube() || in_array($block->getId(), Cache::$config["cancel_block"])) {
                Util::antiBlockGlitch($player);
            }
        } elseif ($session->data["staff_mod"][0]) {
            $event->cancel();
        } elseif ($session->data["cobblestone"] === false && in_array($block->getId(), [BlockLegacyIds::COBBLESTONE, BlockLegacyIds::STONE])) {
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
                } elseif ($entity instanceof AntiBackBallEntity) {
                    $player->setImmobile();

                    $damager->sendMessage(Util::PREFIX . "Vous avez touché §e" . $player->getDisplayName() . " §favec votre antiback ball, il est donc freeze pendant §e2 §fsecondes");
                    $player->sendMessage(Util::PREFIX . "Vous avez été touché par une antiback ball par §e" . $damager->getDisplayName() . " §fvous êtes donc freeze pendant §e2 §fsecondes");

                    Session::get($damager)->setCooldown("combat", 30, [$player->getName()]);
                    Session::get($player)->setCooldown("combat", 30, [$damager->getName()]);

                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player) {
                        if ($player->isOnline()) {
                            $player->setImmobile(false);
                        }
                    }), 2 * 20);
                }
            }
        }
    }
}