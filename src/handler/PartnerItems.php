<?php

namespace Kitmap\handler;

use Kitmap\entity\AntiBackBallEntity;
use Kitmap\entity\SwitcherEntity;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Location;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

class PartnerItems
{
    public static function executeInteractPartnerItem(Player $player, PlayerItemUseEvent $event): bool
    {
        $item = $player->getInventory()->getItemInHand();
        $session = Session::get($player);

        if (is_null($item->getNamedTag()->getTag("partneritem"))) {
            return false;
        } else if (Util::insideZone($player->getPosition(), "spawn")) {
            $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas utilisé cet item au spawn");
            return false;
        } else if ($session->inCooldown("_partneritemblocker")) {
            $player->sendTip(Util::PREFIX . "Veuillez attendre §e" . ($session->getCooldownData("_partneritemblocker")[0] - time()) . " §fseconde(s) avant d'utiliser un partner item");
            return false;
        }

        $name = $item->getNamedTag()->getString("partneritem");

        if (explode(":", Cache::$config["partneritems"][$name])[3] === "2") {
            return false;
        }

        $event->cancel();

        switch ($name) {
            case "timewarp":
                if ($session->inCooldown($name)) {
                    $player->sendMessage(Util::PREFIX . "Veuillez attendre §e" . ($session->getCooldownData($name)[0] - time()) . " §fseconde(s) avant de réutiliser un warp-timer");
                    return true;
                } else {
                    if (!$session->inCooldown("enderpearl")) {
                        $player->sendMessage(Util::PREFIX . "Vous n'avez pas lancé d'enderpearl les §e15 §fdernière secondes");
                        return true;
                    }

                    $position = $session->getCooldownData("enderpearl")[1];
                    $player->sendMessage(Util::PREFIX . "Vous allez être téléporté à l'emplacement de votre dernière enderperl dans §e3 secondes");

                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player, $session, $position) {
                        if ($player->isOnline()) {
                            $player->teleport($position);

                            $session->setCooldown("teleportation_switch", 3);
                            $player->sendMessage(Util::PREFIX . "Vous avez été téléporté à l'emplacement de votre dernière enderpearl");
                        }
                    }), 2 * 20);

                    $session->setCooldown($name, 60 * 2);
                }
                break;
            case "repairstick":
                $armorInventory = $player->getArmorInventory()->getContents();
                $baseInventory = $player->getInventory()->getContents();

                $durableArmorItems = array_filter($armorInventory, fn(Item $item) => $item instanceof Durable && $item->getDamage() > 0);
                $durableBaseItems = array_filter($baseInventory, fn(Item $item) => $item instanceof Durable && $item->getDamage() > 0);

                $durableItems = array_merge($durableArmorItems, $durableBaseItems);

                if (count($durableItems) === 0) {
                    $player->sendMessage(Util::PREFIX . "Aucun item réparable n'a été trouvé dans votre inventaire");
                    return true;
                }

                $randomItem = $durableItems[array_rand($durableItems)] ?? null;

                if ($randomItem instanceof Durable) {
                    if (in_array($randomItem, $armorInventory, true)) {
                        $slot = array_search($randomItem, $armorInventory, true);
                        $randomItem = $randomItem->setDamage(0);

                        if (!is_null($randomItem->getNamedTag()->getTag("cdt"))) {
                            $randomItem->getNamedTag()->removeTag("cdt");
                        }

                        $player->getArmorInventory()->setItem($slot, $randomItem);
                    } else {
                        $slot = array_search($randomItem, $baseInventory, true);
                        $randomItem = $randomItem->setDamage(0);

                        if (!is_null($randomItem->getNamedTag()->getTag("cdt"))) {
                            $randomItem->getNamedTag()->removeTag("cdt");
                        }

                        $player->getInventory()->setItem($slot, $randomItem);
                    }

                    $player->sendMessage(Util::PREFIX . "L'item §e" . $randomItem->getName() . " §fqui est dans votre inventaire a été réparé");
                }
                break;
            case "resistance":
                if ($session->inCooldown($name)) {
                    $player->sendMessage(Util::PREFIX . "Veuillez attendre §e" . ($session->getCooldownData($name)[0] - time()) . " §fsecondes avant d'avoir de nouveau un effet de resistance III");
                    return true;
                } else {
                    $session->setCooldown($name, 60);
                    $seconds = mt_rand(5, 10);

                    $player->getEffects()->add(new EffectInstance(VanillaEffects::RESISTANCE(), 20 * $seconds, 2, false));
                    $player->sendMessage(Util::PREFIX . "Vous venez de recevoir §eresistance III §fpendant " . $seconds . " secondes");
                }
                break;
            case "strength":
                if ($session->inCooldown($name)) {
                    $player->sendMessage(Util::PREFIX . "Veuillez attendre §e" . ($session->getCooldownData($name)[0] - time()) . " §fsecondes avant d'avoir de nouveau un effet de force II");
                    return true;
                } else {
                    $session->setCooldown($name, 60);
                    $seconds = mt_rand(5, 10);

                    $player->getEffects()->add(new EffectInstance(VanillaEffects::STRENGTH(), 20 * $seconds, 1, false));
                    $player->sendMessage(Util::PREFIX . "Vous venez de recevoir §eforce II §fpendant " . $seconds . " secondes");
                }
                break;
            case "focusmode":
                if ($session->inCooldown($name)) {
                    $player->sendMessage(Util::PREFIX . "Veuillez attendre §e" . ($session->getCooldownData($name)[0] - time()) . " §fsecondes avant d'utiliser de nouveau le focus mode");
                    return true;
                } else {
                    $hit = $session->data["last_hit"];

                    if (is_null($hit[0])) {
                        $player->sendMessage(Util::PREFIX . "Vous n'avez frappé aucun joueur depuis votre connexion");
                        return true;
                    } else if (time() - $hit[1] > 60) {
                        $player->sendMessage(Util::PREFIX . "Vous devez avoir tappé un joueur les §e60 §fdernières secondes");
                        return true;
                    } else if (!($target = Main::getInstance()->getServer()->getPlayerExact($hit[0])) instanceof Player) {
                        $player->sendMessage(Util::PREFIX . "Le dernier joueur que vous avez tapé n'est plus en ligne");
                        return true;
                    }

                    $player->sendMessage(Util::PREFIX . "Vous venez d'activer le focus mode, le joueur §e" . $target->getDisplayName() . " §fperdra §e15% §fde vie en plus lorsqu'il sera frappé");
                    $target->sendMessage(Util::PREFIX . "Un joueur a activé le focus mode sur vous, vous prendrez donc §e15% §fde dégat en plus les §e10 §fprochaine secondes");

                    Session::get($target)->setCooldown("_focusmode", 10, [$player->getName()]);
                    $session->setCooldown($name, 60);
                }
                break;
            case "ninjastar":
                if ($session->inCooldown($name)) {
                    $player->sendMessage(Util::PREFIX . "Veuillez attendre §e" . ($session->getCooldownData($name)[0] - time()) . " §fsecondes avant d'utiliser de nouveau une ninjastar");
                    return true;
                } else {
                    $data = $session->getCooldownData("combat");

                    if (!$session->inCooldown("combat") || !($target = Main::getInstance()->getServer()->getPlayerExact($data[1])) instanceof Player) {
                        $player->sendMessage(Util::PREFIX . "Le dernier joueur que vous avez tapé n'est plus en ligne");
                        return true;
                    } else if (15 >= ($data[0] - time())) {
                        $player->sendMessage(Util::PREFIX . "Vous devez avoir été frappé ou devez frapper une personne pour utiliser une ninjastar");
                        return true;
                    }

                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player, $target, $session) {
                        if ($target->isOnline() && $player->isOnline()) {
                            $player->teleport($target->getPosition());
                            $session->setCooldown("teleportation_switch", 3);
                        }
                    }), 3 * 20);

                    $target->sendMessage(Util::PREFIX . "Le joueur §e" . $player->getDisplayName() . " §fva se téléporter sur vous dans §e3 §fsecondes car il a utilisé une ninjastar");
                    $player->sendMessage(Util::PREFIX . "Vous allez être téléporté sur §e" . $target->getDisplayName() . " §fdans §e3 §fsecondes avec votre ninjastar");

                    $session->setCooldown($name, 60);
                }
                break;
            case "switchball":
                if ($player->getWorld()->getFolderName() !== "map") {
                    $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas utiliser de switchball dans ce monde");
                    return true;
                } else if ($session->inCooldown($name)) {
                    $player->sendMessage(Util::PREFIX . "Veuillez attendre §e" . ($session->getCooldownData($name)[0] - time()) . " §fseconde(s) avant de réutiliser une switchball");
                    return true;
                } else {
                    $entity = new SwitcherEntity(Location::fromObject($player->getEyePos(), $player->getWorld(), $player->getLocation()->getYaw(), $player->getLocation()->getPitch()), $player);

                    $entity->setMotion($event->getDirectionVector()->multiply(1.3));
                    $entity->spawnToAll();

                    $session->setCooldown($name, 45);
                }
                break;
            case "antibackball":
                if ($player->getWorld()->getFolderName() !== "map") {
                    $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas utiliser de antiback ball dans ce monde");
                    return true;
                }

                $entity = new AntiBackBallEntity(Location::fromObject($player->getEyePos(), $player->getWorld(), $player->getLocation()->getYaw(), $player->getLocation()->getPitch()), $player);

                $entity->setMotion($event->getDirectionVector()->multiply(1.7));
                $entity->spawnToAll();
                break;
            case "rocket":
                if ($session->inCooldown($name)) {
                    $player->sendMessage(Util::PREFIX . "Veuillez attendre §e" . ($session->getCooldownData($name)[0] - time()) . " §fseconde(s) avant de réutiliser de nouveau un rocket");
                    return true;
                } else {
                    $player->setMotion(new Vector3(0, 3.5, 0));

                    $session->setCooldown($name, 30);
                    $player->sendMessage(Util::PREFIX . "Vous venez d'être expulsé du sol car vous avez utilisé un rocket");
                }
                break;
        }

        $item->pop();
        $player->getInventory()->setItemInHand($item->isNull() ? VanillaItems::AIR() : $item);

        return true;
    }

    public static function createItem(string $name): Item
    {
        if (!isset(Cache::$config["partneritems"][$name])) {
            return VanillaItems::AIR();
        }

        [$itemName, $lore, $customName] = explode(":", Cache::$config["partneritems"][$name]);

        $item = StringToItemParser::getInstance()->parse($itemName) ?? VanillaItems::AIR();

        $item->setCustomName($customName);
        $item->setLore([$lore]);

        $item->getNamedTag()->setString("partneritem", $name);
        $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 10));

        return $item;
    }

    public static function executeHitPartnerItem(Player $player, Player $target): void
    {
        $item = $player->getInventory()->getItemInHand();

        $playerSession = Session::get($player);
        $targetSession = Session::get($target);

        if (is_null($item->getNamedTag()->getTag("partneritem"))) {
            return;
        } else if ($playerSession->inCooldown("_partneritemblocker")) {
            $player->sendTip(Util::PREFIX . "Veuillez attendre §e" . ($playerSession->getCooldownData("_partneritemblocker")[0] - time()) . " §fseconde(s) avant d'utiliser un partner item");
            return;
        }

        $name = $item->getNamedTag()->getString("partneritem");

        if (explode(":", Cache::$config["partneritems"][$name])[3] === "1") {
            return;
        }

        switch ($name) {
            case "antibuild":
                if ($playerSession->inCooldown($name)) {
                    $player->sendMessage(Util::PREFIX . "Veuillez attendre §e" . ($playerSession->getCooldownData($name)[0] - time()) . " §fseconde(s) avant de réutiliser un antibuild");
                    return;
                } else {
                    $playerSession->setCooldown($name, 60);
                    $targetSession->setCooldown("_" . $name, 10);

                    $player->sendMessage(Util::PREFIX . "Vous venez d'utiliser un antibuild sur §e" . $target->getDisplayName());
                    $target->sendMessage(Util::PREFIX . "Le joueur §e" . $player->getDisplayName() . " §fvous ne pouvez donc plus construire pendant 10 secondes");
                }
                break;
            case "pumpkinaxe":
                if ($target->getArmorInventory()->getHelmet()->equals(VanillaBlocks::PUMPKIN()->asItem())) {
                    $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas utiliser une pumpkin axe sur une personne qui a déjà une citrouille sur la tête");
                    return;
                } else if ($playerSession->inCooldown($name)) {
                    $player->sendMessage(Util::PREFIX . "Veuillez attendre §e" . ($playerSession->getCooldownData($name)[0] - time()) . " §fseconde(s) avant de réutiliser une pumpkin axe");
                    return;
                } else {
                    $playerSession->setCooldown($name, 60);

                    $player->sendMessage(Util::PREFIX . "Vous venez d'utiliser une pumpkin axe sur §e" . $target->getDisplayName());
                    $target->sendMessage(Util::PREFIX . "Le joueur §e" . $player->getDisplayName() . " §fvient d'utiliser une pumpkin axe sur vous");

                    $helmet = $target->getArmorInventory()->getHelmet();
                    $target->getArmorInventory()->setHelmet(VanillaBlocks::PUMPKIN()->asItem());

                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($target, $helmet) {
                        if ($target->isOnline()) {
                            $target->getArmorInventory()->setHelmet($helmet);
                            $target->sendMessage(Util::PREFIX . "Vous venez de récuperer votre casque");
                        }
                    }), 5 * 20);
                }
                break;
            case "partneritemblocker":
                if ($playerSession->inCooldown($name)) {
                    $player->sendMessage(Util::PREFIX . "Veuillez attendre §e" . ($playerSession->getCooldownData($name)[0] - time()) . " §fseconde(s) avant de réutiliser un partneritem blocker");
                    return;
                } else {
                    $playerSession->setCooldown($name, 60);
                    $targetSession->setCooldown("_" . $name, 30);

                    $player->sendMessage(Util::PREFIX . "Vous venez d'utiliser un partneritem blocker sur §e" . $target->getDisplayName());
                    $target->sendMessage(Util::PREFIX . "Le joueur §e" . $player->getDisplayName() . " §fvient d'utiliser un partneritem blocker sur vous");
                }
                break;
            case "antipearl":
                if ($playerSession->inCooldown($name)) {
                    $player->sendMessage(Util::PREFIX . "Veuillez attendre §e" . ($playerSession->getCooldownData($name)[0] - time()) . " §fseconde(s) avant de réutiliser un anti perle");
                    return;
                } else {
                    $playerSession->setCooldown($name, 60);
                    $targetSession->setCooldown("_" . $name, 30);

                    $player->sendMessage(Util::PREFIX . "Vous venez d'utiliser un anti perle sur §e" . $target->getDisplayName());
                    $target->sendMessage(Util::PREFIX . "Le joueur §e" . $player->getDisplayName() . " §fvient d'utiliser un anti perle sur vous");
                }
                break;
        }

        $item->pop();
        $player->getInventory()->setItemInHand($item->isNull() ? VanillaItems::AIR() : $item);
    }
}