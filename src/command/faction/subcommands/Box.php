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

class Box extends FactionCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "box",
            "Ouvre le menu des boxs de faction"
        );

        $this->setAliases(["boxs"]);
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

        $form->setTitle("Box");
        $form->setContent(Util::PREFIX . "Cliquez sur le boutton de votre choix");
        $form->addButton("Votre Box");
        $form->addButton("Gérer Votre Box");
        $form->addButton("Visiter une Box");
        $player->sendForm($form);
    }

    private function tpForm(Player $player, string $visit = null): void
    {
        $session = Session::get($player);

        if ($session->inCooldown("teleportation")) {
            $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas executer cette commande en teleportation");
            return;
        } else if (is_null($visit)) {
            $permission = Faction::hasPermission($player, "box");

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

        $name = "box-" . $faction;
        Main::getInstance()->getServer()->getWorldManager()->loadWorld($name);

        if (Main::getInstance()->getServer()->getWorldManager()->isWorldLoaded($name)) {
            Main::getInstance()->getScheduler()->scheduleRepeatingTask(new TeleportationTask(
                $player,
                Main::getInstance()->getServer()->getWorldManager()->getWorldByName($name)->getSpawnLocation()
            ), 20);

            return;
        }

        if (!is_null($visit)) {
            $player->sendMessage(Util::PREFIX . "Cette faction ne possède pas encore de box");
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
                    $player->sendMessage(Util::PREFIX . "Une erreur est surevenue lors de la génération de votre box");
                    return;
                }

                Main::getInstance()->getServer()->getWorldManager()->generateWorld($name, WorldCreationOptions::create()->setSeed(0)->setGeneratorClass($generator->getGeneratorClass()));
                Main::getInstance()->getServer()->getWorldManager()->loadWorld($name);

                $world = Main::getInstance()->getServer()->getWorldManager()->getWorldByName($name);

                if ($world instanceof World) {
                    list($x, $y, $z) = explode(":", Cache::$config["box"][$data]["spawn"]);
                    $world->setSpawnLocation(new Vector3(floatval($x), floatval($y), floatval($z)));
                }
            }

            Main::getInstance()->getServer()->getWorldManager()->loadWorld($name);
            $this->tpForm($player);
        });

        $form->setTitle("Box");
        $form->setContent(Util::PREFIX . "Votre faction ne possède pas encore de box, veuillez choisir un biome pour votre box :");
        $form->addButton("Biome Sakura", 0, "textures/render/cherry_leaves", "cherry");
        $form->addButton("Biome Jungle", 0, "textures/render/jungle_log", "jungle");
        $form->addButton("Biome Boue", 0, "textures/render/mud", "mangrove");
        $form->addButton("Biome Savane", 0, "textures/render/acacia_log", "savana");
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
                    $name = "box-" . $faction;

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

                        Cache::$factions[$faction]["box"]["lock"] = true;
                        $player->sendMessage(Util::PREFIX . "Vous venez de bloqué votre box de faction aux visiteurs");
                    } else if ($data === "unlock") {
                        Cache::$factions[$faction]["box"]["lock"] = false;
                        $player->sendMessage(Util::PREFIX . "Tous le monde peut désormais avoir accès à votre box");
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
                    $name = "box-" . $faction;

                    $world = Main::getInstance()->getServer()->getWorldManager()->getWorldByName($name);

                    if ($world instanceof World) {
                        if ($data === "day") {
                            $world->setTime(World::TIME_DAY);
                            $player->sendMessage(Util::PREFIX . "L'heure de votre bx a été mise à une heure de jour");
                        } else if ($data === "night") {
                            $world->setTime(World::TIME_NIGHT);
                            $player->sendMessage(Util::PREFIX . "L'heure de votre box a été mise à une heure de nuit");
                        } else if ($data === "disable") {
                            $world->stopTime();
                            $player->sendMessage(Util::PREFIX . "Le changement d'heure de votre box a été arrêté");
                        } else if ($data === "enable") {
                            $world->stopTime();
                            $player->sendMessage(Util::PREFIX . "Le changement d'heure de votre box a été activé");
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
                    $name = "box-" . $faction;

                    if ($player->getWorld()->getFolderName() !== $name) {
                        $player->sendMessage(Util::PREFIX . "Vous n'êtes pas dans votre box de faction");
                        return;
                    } else if (!$player->isOnGround()) {
                        $player->sendMessage(Util::PREFIX . "Le point d'apparition de votre box doit être au sol");
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
            $form->setTitle("Box");
            $form->setContent(Util::PREFIX . "Cliquez sur le boutton de votre choix");
            $form->addButton("Agrandir la box", -1, "", "expand");

            if (Faction::isBoxLocked($session->data["faction"])) {
                $form->addButton("Débloqué aux visiteurs", -1, "", "unlock");
            } else {
                $form->addButton("Bloquer aux visiteurs", -1, "", "lock");
            }

            $form->addButton("Mettre le jour", -1, "", "day");
            $form->addButton("Mettre la nuit", -1, "", "night");
            $form->addButton("Arreter le temps", -1, "", "disable");
            $form->addButton("Activer le temps", -1, "", "enable");
            $form->addButton("Définir le point d'apparition", -1, "", "spawn");
            $form->addButton("Supprimer sa box", -1, "", "delete");
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
                $player->sendMessage(Util::PREFIX . "Votre nombre de pièces est inférieur à §e" . $amount);
                return;
            }

            $session->addValue("money", $amount, true);
            Cache::$factions[$faction]["logs"][time()] = "§e" . $player->getName() . " §faugmente la taille de la box";

            Cache::$factions[$faction]["box"]["zone"]["min"] -= 1;
            Cache::$factions[$faction]["box"]["zone"]["max"] += 1;

            Faction::broadcastMessage($faction, "§e[§fF§e] §e" . $player->getName() . " §fvient d'augmenter la taille de la box et a utiliser §e" . Util::formatNumberWithSuffix($amount) . " §fpièces de sa poche");
        });
        $form->setTitle("Box");
        $form->setContent(Util::PREFIX . "Le diamètre de la box sera augmenté de 1 bloc pour §e" . Util::formatNumberWithSuffix($amount) . " pièces §f!\n\n§fL'argent sera déduit de votre poche, alors réunissez l'argent au bon endroit\n\n" . Util::PREFIX . "Êtes vous sur de faire cela?");
        $form->addButton("Oui", -1, "", "yes");
        $form->addButton("Non", -1, "", "no");
        $player->sendForm($form);
    }

    private function getExpandPrice(string $faction): ?int
    {
        $default = Cache::$config["box"]["default-max"];
        $max = Cache::$factions[$faction]["box"]["zone"]["max"] ?? $default;

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

                $permission = Faction::hasPermission($player, "delete-box");

                if (is_null($permission)) {
                    $player->sendMessage(Util::PREFIX . "Vous n'êtes dans aucune faction");
                    return;
                } else if (!$permission) {
                    $player->sendMessage(Util::PREFIX . "Vous ne possèdez pas les permissions necessaire dans votre faction pour faire cela");
                    return;
                }

                Faction::deleteBox($faction);
                $player->sendMessage(Util::PREFIX . "Votre box de faction vient d'être supprimé");
            }
        });
        $form->setTitle("Box");
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
            } else if (Faction::isBoxLocked($faction)) {
                $player->sendMessage(Util::PREFIX . "La faction dont vous vouliez vous téléportez à bloqué les visites");
                return;
            }

            $this->tpForm($player, $faction);
        });

        $form->setTitle("Box");
        $form->addInput(Util::PREFIX . "Veuillez entrer le nom d'une faction");
        $player->sendForm($form);
    }

    protected function prepare(): void
    {
    }
}