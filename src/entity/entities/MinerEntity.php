<?php

namespace Kitmap\entity\entities;

use Kitmap\handler\OtherAPI;
use Kitmap\Session;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use ref\libNpcDialogue\form\NpcDialogueButtonData;
use ref\libNpcDialogue\NpcDialogue;
use Util\util\IdsUtils;

class MinerEntity extends Human
{
    public function attack(EntityDamageEvent $source): void
    {
        if ($source instanceof EntityDamageByEntityEvent) {
            $damager = $source->getDamager();

            if ($damager instanceof Player) {
                $session = Session::get($damager);
                $npcDialogue = new NpcDialogue();

                if ($session->inCooldown("miner_quest")) {
                    $npcDialogue->setDialogueBody("Je t'ai déjà donné une pioche en ilvaïte... Je n'en ai pas pour l'instant pour toi, reviens plus tard.");
                } else {
                    $body = "Tu as besoin d'une pioche en ilvaïte ? Si oui, tu es tombé sur la bonne personne. J'ai besoin d'émeraude pour mon armurie, je peux te faire un échange mais pour cela il te faudra de la force et du courage.\n\nAmène moi §237 §rémeraudes, en échange je te donnerais ce que tu veux.";

                    $npcDialogue->addButton(NpcDialogueButtonData::create()
                        ->setName("Je reviens")
                        ->setClickHandler(function (Player $player): void {
                            $player->sendMessage("§f[§eMineur§f] §e§l» §r§fBon courage !");
                        })
                        ->setForceCloseOnClick(true)
                    );

                    $npcDialogue->addButton(NpcDialogueButtonData::create()
                        ->setName("J'ai cela !")
                        ->setClickHandler(function (Player $player) use ($session): void {
                            if (37 > OtherAPI::getItemCount($player, ItemIds::EMERALD)) {
                                $player->sendMessage("§f[§eMineur§f] §e§l» §r§fTu veux m'arnaquer ? Tu n'as pas les émeraudes que je te demande.");
                                return;
                            }

                            OtherAPI::addItem($player, ItemFactory::getInstance()->get(IdsUtils::ILVAITE_PICKAXE));

                            $player->sendMessage("§f[§eMineur§f] §e§l» §r§fMerci beaucoup pour tes émeraudes ! Tu m'aide beaucoup ! Pour te remercier je te donne ce que tu voulais ! Voila ta pioche en ilvaïte !");
                            $session->setCooldown("miner_quest", 60 * 60 * 2);

                            $player->getInventory()->removeItem(ItemFactory::getInstance()->get(ItemIds::EMERALD, 0, 37));
                        })
                        ->setForceCloseOnClick(true)
                    );

                    if (!$session->inCooldown("diamond_pickaxe")) {
                        $body .= " Pour t'aider je peux te fournir une pioche en diamant";

                        $npcDialogue->addButton(NpcDialogueButtonData::create()
                            ->setName("Une pioche")
                            ->setClickHandler(function (Player $player) use ($session): void {
                                OtherAPI::addItem($player, VanillaItems::DIAMOND_PICKAXE());

                                $player->sendMessage("§f[§eMineur§f] §e§l» §r§fJe t'ai donné ta pioche en diamant ! Bon courage !");
                                $session->setCooldown("diamond_pickaxe", 60 * 15);
                            })
                            ->setForceCloseOnClick(true)
                        );
                    }

                    $npcDialogue->setDialogueBody($body);
                }

                $npcDialogue->setNpcName("Mineur");
                $npcDialogue->setSceneName("Miner");
                $npcDialogue->sendTo($damager, $this);
            }
        }

        $source->cancel();
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $this->setNameTag("Mineur");
        $this->setNameTagAlwaysVisible();
    }
}