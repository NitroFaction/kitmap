<?php

namespace Kitmap\command\faction\subcommands;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use Kitmap\command\faction\FactionCommand;
use Kitmap\handler\Cache;
use Kitmap\handler\Faction;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\task\TeleportationTask;
use Kitmap\Util;
use pocketmine\math\Vector3;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\world\generator\GeneratorManager;
use pocketmine\world\World;
use pocketmine\world\WorldCreationOptions;

class Island extends FactionCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "island",
            "Ouvre le menu des iles de faction"
        );

        $this->setAliases(["is"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args): void
    {
        if ($session->inCooldown("combat")) {
            $sender->sendMessage(Util::PREFIX . "Cette commande est interdite en combat");
            return;
        }

        $this->menuForm($sender);
    }

    private function menuForm(Player $player): void
    {
        $form = new SimpleForm(function (Player $player, mixed $data) {
            if (!is_int($data)) {
                return;
            }

            if ($data === 0) {
                $this->tpForm($player);
            } else if ($data === 1) {
                $this->manageForm($player);
            } else if ($data === 2) {
                $this->visitForm($player);
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
            $permission = Faction::hasPermission($player, "island");

            if (is_null($permission)) {
                $player->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction");
                return;
            } else if (!$permission) {
                $player->sendMessage(Util::PREFIX . "Vous ne possèdez pas les permissions necessaire dans votre faction pour faire cela");
                return;
            }

            $faction = $session->data["faction"];
        } else {
            $faction = $visit;
        }

        $name = "island-" . $faction;
        Main::getInstance()->getServer()->getWorldManager()->loadWorld($name);

        if (Main::getInstance()->getServer()->getWorldManager()->isWorldLoaded($name)) {
            Main::getInstance()->getScheduler()->scheduleRepeatingTask(new TeleportationTask(
                $player,
                Main::getInstance()->getServer()->getWorldManager()->getWorldByName($name)->getSpawnLocation()
            ), 20);

            return;
        }

        if (!is_null($visit)) {
            $player->sendMessage(Util::PREFIX . "Cette faction ne possède pas encore d'ile");
            return;
        }

        $form = new SimpleForm(function (?Player $player, mixed $data) use ($name) {
            if (!is_string($data)) {
                return;
            }

            $world = Main::getInstance()->getServer()->getWorldManager()->getWorldByName($name);

            if (is_null($world)) {
                $generator = GeneratorManager::getInstance()->getGenerator($data);

                if (is_null($generator)) {
                    $player->sendMessage(Util::PREFIX . "Une erreur est surevenue lors de la génération de votre ile");
                    return;
                }

                Main::getInstance()->getServer()->getWorldManager()->generateWorld($name, WorldCreationOptions::create()
                    ->setSeed(0)
                    ->setGeneratorClass($generator->getGeneratorClass())
                    ->setGeneratorOptions(json_encode(Cache::$config["generators"][$data], flags: JSON_THROW_ON_ERROR))
                );

                Main::getInstance()->getServer()->getWorldManager()->loadWorld($name);

                $world = Main::getInstance()->getServer()->getWorldManager()->getWorldByName($name);

                if ($world instanceof World) {
                    $world->setTime(13200);
                    $world->stopTime();

                    list($x, $y, $z) = explode(":", Cache::$config["islands"][$data]["spawn"]);
                    $world->setSpawnLocation(new Vector3(floatval($x), floatval($y), floatval($z)));
                }
            }

            Main::getInstance()->getServer()->getWorldManager()->loadWorld($name);
            $this->tpForm($player);
        });

        $form->setTitle("Ile");
        $form->setContent(Util::PREFIX . "Votre faction ne possède pas encore d'ile, veuillez choisir une ile :");
        $form->addButton("Basique", 0, "textures/render/cherry_leaves", "basic");
        $form->addButton("Generateur", 0, "textures/render/jungle_log", "cobblestone");
        $form->addButton("Cave", 0, "textures/render/mud", "cave");
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
                    if (!Faction::hasFaction($player)) {
                        $player->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction");
                        return;
                    }

                    $this->expandForm($player, $session->data["faction"]);
                    return;
                case "lock":
                case "unlock":
                    $permission = Faction::hasPermission($player, "lock");

                    if (is_null($permission)) {
                        $player->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction");
                        return;
                    } else if (!$permission) {
                        $player->sendMessage(Util::PREFIX . "Vous ne possèdez pas les permissions necessaire dans votre faction pour faire cela");
                        return;
                    }

                    $faction = $session->data["faction"];
                    $name = "island-" . $faction;

                    if ($data === "lock") {
                        $world = Main::getInstance()->getServer()->getWorldManager()->getWorldByName($name);

                        if ($world instanceof World) {
                            foreach ($world->getEntities() as $entity) {
                                if ($entity instanceof Player) {
                                    $entitySession = Session::get($entity);

                                    if ($entitySession->data["faction"] !== $faction) {
                                        $entity->teleport(Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
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
                    $permission = Faction::hasPermission($player, "time");

                    if (is_null($permission)) {
                        $player->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction");
                        return;
                    } else if (!$permission) {
                        $player->sendMessage(Util::PREFIX . "Vous ne possèdez pas les permissions necessaire dans votre faction pour faire cela");
                        return;
                    }

                    $faction = $session->data["faction"];
                    $name = "island-" . $faction;

                    $world = Main::getInstance()->getServer()->getWorldManager()->getWorldByName($name);

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
                    $permission = Faction::hasPermission($player, "set-spawn");

                    if (is_null($permission)) {
                        $player->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction");
                        return;
                    } else if (!$permission) {
                        $player->sendMessage(Util::PREFIX . "Vous ne possèdez pas les permissions necessaire dans votre faction pour faire cela");
                        return;
                    }

                    $faction = $session->data["faction"];
                    $name = "island-" . $faction;

                    if ($player->getWorld()->getFolderName() !== $name) {
                        $player->sendMessage(Util::PREFIX . "Vous n'êtes pas dans votre ile de faction");
                        return;
                    } else if (!$player->isOnGround()) {
                        $player->sendMessage(Util::PREFIX . "Le point d'apparition de votre ile doit être au sol");
                        return;
                    }

                    $world = Main::getInstance()->getServer()->getWorldManager()->getWorldByName($name);

                    if ($world instanceof World) {
                        $world->setSpawnLocation($player->getPosition());
                        $player->sendMessage(Util::PREFIX . "Le point d'apparition de votre monde vient d'être mis à jour !");
                    }
                    return;
            }

            $this->verifForm($player, $data);
        });

        if (Faction::hasFaction($player)) {
            $form->setTitle("Ile");
            $form->setContent(Util::PREFIX . "Cliquez sur le boutton de votre choix");
            $form->addButton("Agrandir l'ile", -1, "", "expand");

            if (Faction::isIslandLocked($session->data["faction"])) {
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
            if (!is_string($data) || $data != "yes") {
                return;
            } else if (!Faction::hasFaction($player)) {
                $player->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction");
                return;
            }

            $session = Session::get($player);

            $faction = $session->data["faction"];
            $amount = $this->getExpandPrice($faction);

            if ($amount > $session->data["money"]) {
                $player->sendMessage(Util::PREFIX . "Votre nombre de pièces est inférieur à §9" . $amount);
                return;
            }

            $session->addValue("money", $amount, true);
            Cache::$factions[$faction]["logs"][time()] = "§9" . $player->getName() . " §faugmente la taille de l'ile";

            Cache::$factions[$faction]["island"]["zone"]["min"] -= 1;
            Cache::$factions[$faction]["island"]["zone"]["max"] += 1;

            Faction::broadcastMessage($faction, "§9[§fF§9] §9" . $player->getName() . " §fvient d'augmenter la taille de l'ile et a utiliser §9" . Util::formatNumberWithSuffix($amount) . " §fpièces de sa poche");
        });
        $form->setTitle("Ile");
        $form->setContent(Util::PREFIX . "Le diamètre de l'ile sera augmenté de 1 bloc pour §9" . Util::formatNumberWithSuffix($amount) . " pièces §f!\n\n§fL'argent sera déduit de votre poche, alors réunissez l'argent au bon endroit\n\n" . Util::PREFIX . "Êtes vous sur de faire cela?");
        $form->addButton("Oui", -1, "", "yes");
        $form->addButton("Non", -1, "", "no");
        $player->sendForm($form);
    }

    private function getExpandPrice(string $faction): ?int
    {
        $default = Cache::$config["islands"]["default-max"];
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
                $faction = $session->data["faction"];

                $permission = Faction::hasPermission($player, "delete-island");

                if (is_null($permission)) {
                    $player->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction");
                    return;
                } else if (!$permission) {
                    $player->sendMessage(Util::PREFIX . "Vous ne possèdez pas les permissions necessaire dans votre faction pour faire cela");
                    return;
                }

                Faction::deleteIsland($faction);
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

            if (!Faction::exists($faction)) {
                $player->sendMessage(Util::PREFIX . "Cette faction n'existe pas (verifiez les majuscules)");
                return;
            } else if (Faction::isIslandLocked($faction)) {
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