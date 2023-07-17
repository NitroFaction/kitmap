<?php

namespace NCore\command\sub\faction;

use CortexPE\Commando\BaseSubCommand;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use NCore\Base;
use NCore\handler\Cache;
use NCore\handler\FactionAPI;
use NCore\handler\OtherAPI;
use NCore\Session;
use NCore\task\TeleportationTask;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\generator\GeneratorManager;
use pocketmine\world\World;
use pocketmine\world\WorldCreationOptions;

class Island extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "island", "Accès à son ile commune", ["is"]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->inCooldown("combat")) {
                $sender->sendMessage(Util::PREFIX . "Cette commande est interdite en combat");
                return;
            }

            $this->menuForm($sender);
        }
    }

    private function menuForm(Player $player): void
    {
        $form = new SimpleForm(function (Player $player, mixed $data) {
            if (!is_int($data)) {
                return;
            }

            switch ($data) {
                case 0:
                    $this->tpForm($player);
                    return;
                case 1:
                    $this->manageForm($player);
                    return;
                case 2:
                    $this->visitForm($player);
                    return;
            }
        });

        $form->setTitle("Ile");
        $form->setContent(Util::PREFIX . "Cliquez sur le boutton de votre choix");
        $form->addButton("Votre Ile");
        $form->addButton("Gérer Votre Ile");
        $form->addButton("Visiter une Ile");
        $player->sendForm($form);
    }

    private function tpForm(Player $player, string $visit = null): void
    {
        $session = Session::get($player);

        if ($session->inCooldown("teleportation")) {
            $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas executer cette commande en teleportation");
            return;
        } else if (is_null($visit)) {
            $permission = FactionAPI::hasPermission($player, "island");

            if (is_null($permission)) {
                $player->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction");
                return;
            } else if (!$permission) {
                $player->sendMessage(Util::PREFIX . "Vous ne possèdez pas les permissions necessaire dans votre faction pour faire cela");
                return;
            }

            $faction = $session->data["player"]["faction"];
        } else {
            $faction = $visit;
        }

        $name = "island-" . $faction;
        Base::getInstance()->getServer()->getWorldManager()->loadWorld($name);

        if (Base::getInstance()->getServer()->getWorldManager()->isWorldLoaded($name)) {
            $time = OtherAPI::getTpTime($player);

            $player->sendMessage(Util::PREFIX . "Vous allez être teleporté dans §e" . max($time, 0) . " §fseconde(s), veuillez ne pas bouger");
            $player->getEffects()->add(new EffectInstance(VanillaEffects::BLINDNESS(), 20 * ($time + 1), 1, false));

            Base::getInstance()->getScheduler()->scheduleRepeatingTask(new TeleportationTask($player, Base::getInstance()->getServer()->getWorldManager()->getWorldByName($name)->getSpawnLocation()), 20);
            $session->setCooldown("teleportation", $time, [OtherAPI::getPlace($player)]);

            return;
        }

        if (!is_null($visit)) {
            $player->sendMessage(Util::PREFIX . "Cette faction ne possède pas encore d'île");
            return;
        }

        $form = new SimpleForm(function (?Player $player, mixed $data) use ($name) {
            if (!is_string($data)) {
                return;
            }

            $world = Base::getInstance()->getServer()->getWorldManager()->getWorldByName($name);

            if (is_null($world)) {
                $generator = GeneratorManager::getInstance()->getGenerator($data);

                if (is_null($generator)) {
                    $player->sendMessage(Util::PREFIX . "Une erreur est surevenue lors de la génération de votre ile");
                    return;
                }

                Base::getInstance()->getServer()->getWorldManager()->generateWorld($name, WorldCreationOptions::create()->setSeed(0)->setGeneratorClass($generator->getGeneratorClass()));
                Base::getInstance()->getServer()->getWorldManager()->loadWorld($name);

                $world = Base::getInstance()->getServer()->getWorldManager()->getWorldByName($name);

                if ($world instanceof World) {
                    list($x, $y, $z) = explode(":", Cache::$config["islands"][$data]["spawn"]);
                    $world->setSpawnLocation(new Vector3(floatval($x), floatval($y), floatval($z)));
                }
            }

            Base::getInstance()->getServer()->getWorldManager()->loadWorld($name);
            $this->tpForm($player);
        });

        $form->setTitle("Ile");
        $form->setContent(Util::PREFIX . "Votre faction ne possède pas encore d'île, veuillez choisir une île :");
        $form->addButton("La Naturel", 0, "textures/ids/18-0", "one_is");
        $form->addButton("La Cave", 0, "textures/ids/1-0", "two_is");
        $form->addButton("La Basique", 0, "textures/ids/2-0", "three_is");
        $player->sendForm($form);
    }

    private function manageForm(Player $player): void
    {
        $session = Session::get($player);

        $form = new SimpleForm(function (Player $player, mixed $data) use ($session) {
            if (!is_string($data)) {
                return;
            }

            switch ($data) {
                case "expand":
                    $permission = FactionAPI::hasPermission($player, "expand");

                    if (is_null($permission)) {
                        $player->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction");
                        return;
                    } else if (!$permission) {
                        $player->sendMessage(Util::PREFIX . "Vous ne possèdez pas les permissions necessaire dans votre faction pour faire cela");
                        return;
                    }

                    $this->expandForm($player, $session->data["player"]["faction"]);
                    return;
                case "lock":
                case "unlock":
                    $permission = FactionAPI::hasPermission($player, "lock");

                    if (is_null($permission)) {
                        $player->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction");
                        return;
                    } else if (!$permission) {
                        $player->sendMessage(Util::PREFIX . "Vous ne possèdez pas les permissions necessaire dans votre faction pour faire cela");
                        return;
                    }

                    $faction = $session->data["player"]["faction"];
                    $name = "island-" . $faction;

                    if ($data === "lock") {
                        $world = Base::getInstance()->getServer()->getWorldManager()->getWorldByName($name);

                        if ($world instanceof World) {
                            foreach ($world->getEntities() as $entity) {
                                if ($entity instanceof Player) {
                                    $entitySession = Session::get($entity);

                                    if ($entitySession->data["player"]["faction"] !== $faction) {
                                        $entity->teleport(Base::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
                                    }
                                }
                            }
                        }

                        Cache::$factions[$faction]["island"]["lock"] = true;
                        $player->sendMessage(Util::PREFIX . "Vous venez de bloqué votre ile de faction aux visiteurs");
                    } else if ($data === "unlock") {
                        Cache::$factions[$faction]["island"]["lock"] = false;
                        $player->sendMessage(Util::PREFIX . "Tous le monde peut désormais avoir accès à votre ile");
                    }
                    return;
                case "day":
                case "night":
                case "disable":
                case "enable":
                    $permission = FactionAPI::hasPermission($player, "time");

                    if (is_null($permission)) {
                        $player->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction");
                        return;
                    } else if (!$permission) {
                        $player->sendMessage(Util::PREFIX . "Vous ne possèdez pas les permissions necessaire dans votre faction pour faire cela");
                        return;
                    }

                    $faction = $session->data["player"]["faction"];
                    $name = "island-" . $faction;

                    $world = Base::getInstance()->getServer()->getWorldManager()->getWorldByName($name);

                    if ($world instanceof World) {
                        if ($data === "day") {
                            $world->setTime(World::TIME_DAY);
                            $player->sendMessage(Util::PREFIX . "L'heure de votre ile a été mise à une heure de jour");
                        } else if ($data === "night") {
                            $world->setTime(World::TIME_NIGHT);
                            $player->sendMessage(Util::PREFIX . "L'heure de votre ile a été mise à une heure de nuit");
                        } else if ($data === "disable") {
                            $world->stopTime();
                            $player->sendMessage(Util::PREFIX . "Le changement d'heure de votre ile a été arrêté");
                        } else if ($data === "enable") {
                            $world->stopTime();
                            $player->sendMessage(Util::PREFIX . "Le changement d'heure de votre ile a été activé");
                        }
                    }
                    return;
                case "spawn":
                    $permission = FactionAPI::hasPermission($player, "set-spawn");

                    if (is_null($permission)) {
                        $player->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction");
                        return;
                    } else if (!$permission) {
                        $player->sendMessage(Util::PREFIX . "Vous ne possèdez pas les permissions necessaire dans votre faction pour faire cela");
                        return;
                    }

                    $faction = $session->data["player"]["faction"];
                    $name = "island-" . $faction;

                    if ($player->getWorld()->getFolderName() !== $name) {
                        $player->sendMessage(Util::PREFIX . "Vous n'êtes pas dans votre ile de faction");
                        return;
                    } else if (!$player->isOnGround()) {
                        $player->sendMessage(Util::PREFIX . "Votre point de d'apparition d'ile doit être sur un sol");
                        return;
                    }

                    $world = Base::getInstance()->getServer()->getWorldManager()->getWorldByName($name);

                    if ($world instanceof World) {
                        $world->setSpawnLocation($player->getPosition());
                        $player->sendMessage(Util::PREFIX . "Le point d'apparition de votre monde vient d'être mis à jour !");
                    }
                    return;
            }

            $this->verifForm($player, $data);
        });

        if (FactionAPI::hasFaction($player)) {
            $form->setTitle("Ile");
            $form->setContent(Util::PREFIX . "Cliquez sur le boutton de votre choix");
            $form->addButton("Agrandir l'île", -1, "", "expand");

            if (FactionAPI::isIslandLocked($session->data["player"]["faction"])) {
                $form->addButton("Débloqué aux visiteurs", -1, "", "unlock");
            } else {
                $form->addButton("Bloquer aux visiteurs", -1, "", "lock");
            }

            $form->addButton("Mettre le jour", -1, "", "day");
            $form->addButton("Mettre la nuit", -1, "", "night");
            $form->addButton("Arreter le temps", -1, "", "disable");
            $form->addButton("Activer le temps", -1, "", "enable");
            $form->addButton("Définir le point d'apparition", -1, "", "spawn");
            $form->addButton("Supprimer son ile", -1, "", "delete");
            $player->sendForm($form);
        }
    }

    private function expandForm(Player $player, string $faction): void
    {
        $amount = $this->getExpandPrice($faction);

        $form = new SimpleForm(function (Player $player, mixed $data) {
            $permission = FactionAPI::hasPermission($player, "expand");

            if (!is_string($data) || $data != "yes") {
                return;
            } else if (is_null($permission)) {
                $player->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction");
                return;
            } else if (!$permission) {
                $player->sendMessage(Util::PREFIX . "Vous ne possèdez pas les permissions necessaire dans votre faction pour faire cela");
                return;
            }

            $faction = Session::get($player)->data["player"]["faction"];
            $amount = $this->getExpandPrice($faction);

            if ($amount > Cache::$factions[$faction]["money"]) {
                $player->sendMessage(Util::PREFIX . "L'argent dans la banque de faction est infèrieur à §e" . $amount);
                return;
            }

            Cache::$factions[$faction]["money"] -= $amount;
            Cache::$factions[$faction]["logs"][time()] = "§e" . $player->getName() . " §faugmente la taille de l'ile";

            Cache::$factions[$faction]["island"]["zone"]["min"] -= 1;
            Cache::$factions[$faction]["island"]["zone"]["max"] += 1;

            FactionAPI::broadcastMessage($faction, "§e[§fF§e] §e" . $player->getName() . " §fvient d'augmenter la taille de l'ile et a utiliser §e" . OtherAPI::format($amount) . " §fpièces de la banque de faction");
        });
        $form->setTitle("Ile");
        $form->setContent(Util::PREFIX . "Le diamètre de l'île sera augmenté de 1 bloc pour §e" . OtherAPI::format($amount) . " pièces §f!\n\n§fL'argent sera déduit de la banque de faction, pour déposer dans la banque de l'argent faites §e/f deposit\n\n" . Util::PREFIX . "Êtes vous sur de faire cela?");
        $form->addButton("Oui", -1, "", "yes");
        $form->addButton("Non", -1, "", "no");
        $player->sendForm($form);
    }

    private function getExpandPrice(string $faction): ?int
    {
        $default = Cache::$config["default_max"];
        $max = Cache::$factions[$faction]["island"]["zone"]["max"] ?? $default;

        return is_null($max) ? $max : ($max - ($default - 1)) * 5000;
    }

    private function verifForm(Player $player, string $option): void
    {
        $form = new SimpleForm(function (Player $player, mixed $data) use ($option) {
            if (!is_string($data) || $data != "yes") {
                return;
            }

            if ($option === "delete") {
                $session = Session::get($player);
                $faction = $session->data["player"]["faction"];

                $permission = FactionAPI::hasPermission($player, "delete-island");

                if (is_null($permission)) {
                    $player->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction");
                    return;
                } else if (!$permission) {
                    $player->sendMessage(Util::PREFIX . "Vous ne possèdez pas les permissions necessaire dans votre faction pour faire cela");
                    return;
                }

                FactionAPI::deleteIsland($faction);
                $player->sendMessage(Util::PREFIX . "Votre ile de faction vient d'être supprimé");
            }
        });
        $form->setTitle("Ile");
        $form->setContent(Util::PREFIX . "Êtes vous sur de faire cela?");
        $form->addButton("Oui", -1, "", "yes");
        $form->addButton("Non", -1, "", "no");
        $player->sendForm($form);
    }

    private function visitForm(Player $player): void
    {
        $form = new CustomForm(function (Player $player, mixed $data) {
            if (!is_array($data)) {
                return;
            }
            $faction = strtolower($data[0]);

            if (!FactionAPI::exist($faction)) {
                $player->sendMessage(Util::PREFIX . "Cette faction n'existe pas (verifiez les majuscules)");
                return;
            } else if (FactionAPI::isIslandLocked($faction)) {
                $player->sendMessage(Util::PREFIX . "La faction dont vous vouliez vous téléportez à bloqué les visites");
                return;
            }

            $this->tpForm($player, $faction);
        });

        $form->setTitle("Ile");
        $form->addInput(Util::PREFIX . "Veuillez entrer le nom d'une faction");
        $player->sendForm($form);
    }

    protected function prepare(): void
    {
    }
}