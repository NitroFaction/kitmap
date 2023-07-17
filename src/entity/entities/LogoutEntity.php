<?php

namespace NCore\entity\entities;

use NCore\Base;
use NCore\command\staff\tool\LastInventory;
use NCore\handler\Cache;
use NCore\handler\FactionAPI;
use NCore\handler\OtherAPI;
use NCore\handler\SanctionAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\ItemIds;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\player\Player;

class LogoutEntity extends Human
{
    public bool $killed = false;

    public ?string $player = null;
    private ?string $faction = null;

    private int $time = 600;

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        if (
            $this->closed ||
            $this->player === null ||
            (Base::getInstance()->getServer()->getPlayerExact($this->player)) !== null ||
            $this->time-- <= 0
        ) {
            $this->killed = true;
            $this->time = 0;

            $this->close();
            return false;
        }

        $this->setNameTag("§7" . $this->player . " [" . $this->getHealth() . " §c❤§7] " . SanctionAPI::format($this->time / 20, 1));
        return parent::entityBaseTick($tickDiff);
    }

    public function attack(EntityDamageEvent $source): void
    {
        if ($this->killed || $this->time <= 0) {
            return;
        }

        $damager = null;

        if (!is_null($this->faction) && !FactionAPI::exist($this->faction)) {
            $this->faction = null;
        }

        if ($source instanceof EntityDamageByEntityEvent) {
            $damager = $source->getDamager();

            if ($damager instanceof Player && FactionAPI::hasFaction($damager) && Session::get($damager)->data["player"]["faction"] === $this->faction) {
                $source->cancel();
                return;
            }
        }

        if ($this->getHealth() >= $source->getFinalDamage()) {
            parent::attack($source);
            return;
        }

        if ($damager instanceof Player) {
            $session = Session::get($damager);

            $pot1 = OtherAPI::getInventoryItemCount($this->getInventory(), ItemIds::SPLASH_POTION, 22);
            $pot2 = OtherAPI::getItemCount($damager, ItemIds::SPLASH_POTION, 22);

            Base::getInstance()->getLogger()->info($this->player . " (PNJ) a été tué par " . $damager->getDisplayName() . " (" . $damager->getName() . ")");
            Base::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§e" . $this->player . "[§7" . $pot1 . "§e] §fa été tué par le joueur §e" . $damager->getDisplayName() . "[§7" . $pot2 . "§e]");

            $session->addValue("kill", 1);
            $session->addValue("killstreak", 1);

            if (FactionAPI::hasFaction($damager)) FactionAPI::addPower($session->data["player"]["faction"], 6);
            if (!is_null($this->faction)) FactionAPI::addPower($this->faction, -4);

            if ($session->data["player"]["killstreak"] % 5 == 0) {
                Base::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le joueur §e" . $damager->getName() . " §fa fait §e" . $session->data["player"]["killstreak"] . " §fkill sans mourrir !");
            }
        } else {
            Base::getInstance()->getLogger()->info($this->player . " (PNJ) est mort (" . $source->getCause() . ")");
        }

        $file = Util::getFile("players/" . strtolower($this->player));
        $damager = ($damager instanceof Player) ? $damager : null;

        LastInventory::saveInventory($this->player, $damager, $this->getInventory(), $this->getArmorInventory(), $this->getXpManager()->getCurrentTotalXp(), $file->get("killstreak"));

        $file->set("death", $file->get("death", 0) + 1);
        $file->set("killstreak", 0);
        $file->save();

        $nbt = Base::getInstance()->getServer()->getOfflinePlayerData($this->player);
        $spawn = Base::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation();

        $nbt->setString("Level", $spawn->getWorld()->getFolderName());
        $nbt->setTag("Inventory", new ListTag([], NBT::TAG_Compound));
        $nbt->setTag("ActiveEffects", new ListTag([], NBT::TAG_Compound));
        $nbt->setFloat("Health", 20);
        $nbt->setInt("XpLevel", 0);

        $nbt->setTag("Pos", new ListTag([
            new DoubleTag($spawn->getFloorX()),
            new DoubleTag($spawn->getFloorY()),
            new DoubleTag($spawn->getFloorZ())
        ], NBT::TAG_Double));

        Base::getInstance()->getServer()->saveOfflinePlayerData($this->player, $nbt);

        $this->onDeath();
        $this->killed = true;
    }

    public function initEntityB(Player $player): void
    {
        FactionAPI::hasFaction($player);

        $this->player = $player->getName();
        $this->faction = Session::get($player)->data["player"]["faction"];

        Cache::$logouts[$this->player] = $this;

        $this->getInventory()->setContents($player->getInventory()->getContents());
        $this->getArmorInventory()->setContents($player->getArmorInventory()->getContents());

        $this->getXpManager()->setCurrentTotalXp($player->getXpManager()->getCurrentTotalXp());
    }
}