<?php

namespace Kitmap\block;

use jojoe77777\FormAPI\SimpleForm;
use Kitmap\Util;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Durable;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;

class Anvil extends Block
{
    public function onInteract(PlayerInteractEvent $event): bool
    {
        $player = $event->getPlayer();

        if (!$player->isSneaking() && $event->getAction() === $event::RIGHT_CLICK_BLOCK) {
            Util::removeCurrentWindow($player);

            $this->openAnvil($player);
            $event->cancel();

            return true;
        }

        return false;
    }

    private function openAnvil(Player $player): void
    {
        $item = $player->getInventory()->getItemInHand();

        if (!$item instanceof Durable) {
            $player->sendMessage(Util::PREFIX . "L'item dans votre main ne peut pas être réparé");
            return;
        }

        $form = new SimpleForm(function (Player $player, mixed $data) {
            if (!is_int($data)) {
                return;
            }

            self::confirmationForm($player, $data);
        });
        $form->setTitle("Enclume");
        $form->setContent(Util::PREFIX . "Cliquez sur le boutton de votre choix");
        $form->addButton("Réparer avec\n§915 levels");
        $form->addButton("Réparer avec\n§910 émeraudes");
        $player->sendForm($form);
    }

    private function confirmationForm(Player $player, int $category): void
    {
        $form = new SimpleForm(function (Player $player, mixed $data) use ($category) {
            $item = $player->getInventory()->getItemInHand();

            if (!is_string($data) || $data != "yes") {
                return;
            } else if (!$item instanceof Durable) {
                $player->sendMessage(Util::PREFIX . "L'item dans votre main ne peut pas être réparé");
                return;
            }

            switch ($category) {
                case 0:
                    if (15 > $player->getXpManager()->getXpLevel()) {
                        $player->sendMessage(Util::PREFIX . "Vous n'avez pas assez de niveaux pour réparé votre item");
                        return;
                    }

                    $player->getXpManager()->setXpLevel($player->getXpManager()->getXpLevel() - 15);
                    break;
                case 1:
                    if (10 > Util::getItemCount($player, VanillaItems::EMERALD())) {
                        $player->sendMessage(Util::PREFIX . "Vous n'avez pas assez d'émeraudes pour réparé votre item");
                        return;
                    }

                    $player->getInventory()->removeItem(VanillaItems::EMERALD()->setCount(10));
                    break;
            }

            $item->setDamage(0);

            if (!is_null($item->getNamedTag()->getTag("cdt"))) {
                $item->getNamedTag()->removeTag("cdt");
            }

            $player->getInventory()->setItemInHand($item);


            $player->sendMessage(Util::PREFIX . "Vous venez de réparer l'item dans votre main");
        });
        $form->setTitle("Enclume");
        $form->setContent(Util::PREFIX . "Êtes vous sur de réparer l'item dans votre main ?");
        $form->addButton("Oui", -1, "", "yes");
        $form->addButton("Non", -1, "", "no");
        $player->sendForm($form);
    }
}