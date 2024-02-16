<?php

namespace Kitmap\block;

use jojoe77777\FormAPI\SimpleForm;
use Kitmap\handler\Cache;
use Kitmap\handler\Faction;
use Kitmap\handler\trait\CooldownTrait;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\block\Block as PmBlock;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\world\format\Chunk;
use pocketmine\world\particle\BubbleParticle;
use pocketmine\world\Position;
use pocketmine\world\sound\AmethystBlockChimeSound;

class ChunkBuster extends Block
{
    public function getDrops(PmBlock $block, Item $item, Player $player = null): ?array
    {
        return [];
    }

    public function getXpDropAmount(): ?int
    {
        return 0;
    }

    public function onInteract(PlayerInteractEvent $event): bool
    {
        $player = $event->getPlayer();

        if ($event->getAction() !== $event::RIGHT_CLICK_BLOCK) {
            return true;
        }

        $event->cancel();

        if ($this->inCooldown($player)) {
            return true;
        }

        $this->setCooldown($player, 1);
        $session = Session::get($player);

        if ($session->inCooldown("combat")) {
            $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas utiliser de chunkbuster en combat");
            return true;
        } else if (!Faction::hasFaction($player)) {
            $player->sendMessage(Util::PREFIX . "Ce chunkbuster ne vous appartient pas");
            return true;
        }

        $faction = Faction::hasPermission($player, "chunk-buster");

        if (!$faction) {
            $player->sendMessage(Util::PREFIX . "Vous n'avez pas la permission d'utiliser un chunk buster dans votre claim");
            return true;
        } else if (Faction::inClaim($player->getPosition()->getFloorX(), $player->getPosition()->getFloorZ())[1] !== $session->data["faction"]) {
            $player->sendMessage(Util::PREFIX . "Le chunk buster doit être dans votre claim pour que vous puissiez l'utiliser");
            return true;
        }

        $this->chunkBusterForm($player, $session->data["faction"], $event->getBlock()->getPosition());
        return true;
    }

    public function chunkBusterForm(Player $player, string $faction, Position $position): void
    {
        $form = new SimpleForm(function (Player $player, mixed $data) use ($position, $faction) {
            if (!is_string($data)) {
                return;
            }

            $this->confirmationForm($player, $data, $faction, $position);
        });
        $form->setTitle("Chunk Buster");
        $form->setContent(Util::PREFIX . "Cliquez sur le bouton de votre choix (§9vide = 0 bloc§f, §9entier = un claim de base neuf§f)");

        foreach (Cache::$config["chunk-buster"] as $name => $chunk) {
            $form->addButton(ucfirst($name), label: $name);
        }

        $player->sendForm($form);
    }

    public function confirmationForm(Player $player, string $data, string $faction, Position $position): void
    {
        $chunk = Cache::$config["chunk-buster"][$data] ?? null;

        if (is_null($chunk)) {
            return;
        }

        $form = new SimpleForm(function (Player $player, mixed $confirm) use ($data, $chunk, $faction, $position) {
            if ($confirm !== "yes" || !Faction::exists($faction)) {
                return;
            }

            $world = Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld();

            if ($world->getBlock($position)->getTypeId() !== VanillaBlocks::SMOKER()->getTypeId()) {
                $player->sendMessage(Util::PREFIX . "Le chunkbuster n'est plus là, il ne peut donc pas agir");
                return;
            }

            [$newChunkX, $newChunkZ] = explode(":", $chunk);
            $newChunk = clone $world->loadChunk(intval($newChunkX), intval($newChunkZ));

            if (!$newChunk instanceof Chunk) {
                return;
            }

            $chunk = Cache::$factions[$faction]["claim"];

            if (is_null($chunk)) {
                $player->sendMessage(Util::PREFIX . "Vous ne possèdez aucun claim");
                return;
            }

            [$chunkX, $chunkZ] = explode(":", $chunk);
            $world->setChunk(intval($chunkX), intval($chunkZ), $newChunk);

            $world->setBlock($position, VanillaBlocks::AIR());

            $world->addParticle($position, new BubbleParticle());
            $world->addSound($position, new AmethystBlockChimeSound());

            Cache::$factions[$faction]["logs"][time()] = "§9" . $player->getName() . " §fa changé le claim avec un chunkbuster";
            Faction::broadcastMessage($faction, "§9[§fF§r§9] §9" . $player->getName() . " §fvient de remplacer le claim par un chunk §9" . ucfirst($data) . " §f!");
        });
        $form->setTitle("Chunk Buster");
        $form->setContent(Util::PREFIX . "Êtes vous sûr de remplacer votre claim par un chunk §9" . ucfirst($data) . " §f?\n\nVotre chunk buster disparaitra en cliquant sur confirmer ainsi que tout les coffres, blocs de votre claim actuel\n\nAucun retour en arrière possible");
        $form->addButton("Confirmer", label: "yes");
        $form->addButton("Annuler", label: "no");
        $player->sendForm($form);
    }

    public function onPlace(BlockPlaceEvent $event): bool
    {
        $player = $event->getPlayer();

        if ($player->getWorld() !== Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()) {
            $event->cancel();
            $player->sendMessage(Util::PREFIX . "Les chunkbuster ne sont utilisable que dans les mondes des claims");
        }

        return true;
    }
}