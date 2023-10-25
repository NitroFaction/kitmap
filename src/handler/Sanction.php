<?php

namespace Kitmap\handler;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use Kitmap\handler\discord\Discord;
use Kitmap\handler\discord\EmbedBuilder;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\player\Player;

class Sanction
{
    public static function chooseSanction(Player $player, string $target): void
    {
        $form = new SimpleForm(function (Player $player, mixed $data) use ($target) {
            if (!is_string($data)) {
                return;
            }

            $player->chat("/" . $data . " \"" . $target . "\"");
        });
        $form->setTitle("Sanction");
        $form->setContent(Util::PREFIX . "Cliquez sur le bouton de votre choix");
        $form->addButton("Ban", -1, "", "ban");
        $form->addButton("Kick", -1, "", "kick");
        $form->addButton("Mute", -1, "", "mute");
        $player->sendForm($form);
    }

    public static function sanctionForm(Player $player, string $target, string $type): void
    {
        $form = new SimpleForm(function (Player $player, mixed $data) use ($target, $type) {
            if (!is_string($data)) {
                return;
            }

            if ($data === "other") {
                self::customSanction($player, $target, $type, $data);
            } else if ($type === "kick") {
                self::sanctionPlayer($player, $target, $type, $data, 0);
            } else {
                self::timeForm($player, $target, $type, $data);
            }
        });

        foreach (array_keys(Cache::$config["sanctions"][$type]) as $key) {
            $form->addButton($key, -1, "", $key);
        }

        $form->setTitle(ucfirst($type));
        $form->setContent(Util::PREFIX . "Cliquez sur le bouton de votre choix");
        $form->addButton("Autre", -1, "", "other");
        $player->sendForm($form);
    }

    private static function customSanction(Player $player, string $target, string $type, string $sanction): void
    {
        $form = new CustomForm(function (Player $player, mixed $data) use ($target, $type, $sanction) {
            if (!is_array($data) || !isset($data[0])) {
                return;
            }

            if ($type === "kick") {
                self::sanctionPlayer($player, $target, $type, $data[0], 0);
            } else {
                if (!isset($data[1])) {
                    goto error;
                }

                $time = strtolower($data[1]);

                $letter = strtolower(substr($time, -1));
                $number = substr($time, 0, -1);

                if (!in_array($letter, ["d", "h", "m", "s"]) || !is_numeric($number)) {
                    goto error;
                }

                $time = match ($letter) {
                    "d" => $number * 60 * 60 * 24,
                    "h" => $number * 60 * 60,
                    "m" => $number * 60,
                    "s" => $number,
                    default => 0,
                };

                if (is_int($time)) {
                    self::sanctionPlayer($player, $target, $type, $data[0], $time);
                    return;
                }

                error:
                $player->sendMessage(Util::PREFIX . "Le temps indiqué est invalide");
            }
        });

        $form->setTitle(ucfirst($type));
        $form->addInput("Raison");
        if ($type !== "kick") {
            $form->addInput("Temps");
        }
        $player->sendForm($form);
    }

    private static function sanctionPlayer(Player $player, string $target, string $type, string $sanction, int $time): void
    {
        if ($time === -1) {
            $time = 24 * 60 * 60 * 999;
        }

        switch ($type) {
            case "ban":
                self::banPlayer($player->getName(), $target, $sanction, $time);
                break;
            case "kick":
                self::kickPlayer($player, $target, $sanction);
                break;
            case "mute":
                self::mutePlayer($player, $target, $sanction, $time);
                break;
        }
    }

    public static function banPlayer(string $player, string $target, string $sanction, int $time): void
    {
        /** @noinspection PhpDeprecationInspection */
        $targetPlayer = Main::getInstance()->getServer()->getPlayerByPrefix($target);
        $format = Util::formatDurationFromSeconds($time);

        if ($targetPlayer instanceof Player) {
            $data = Session::get($targetPlayer)->data;
            $target = strtolower($targetPlayer->getName());

            $targetPlayer->kick("§fVous êtes banni de nitrofaction.\n\n§fTemps restant: §6" . $format . "\n§fRaison: §6" . $sanction);
            Main::getInstance()->getServer()->getNetwork()->blockAddress($targetPlayer->getNetworkSession()->getIp(), min(300, $time));
        } else {
            $file = Util::getFile("data/players/" . $target);
            $data = $file->getAll();
        }

        foreach (Cache::$config["saves"] as $column) {
            foreach ($data[$column] ?? [] as $datum) {
                Cache::$bans[$datum] = [$player, time() + $time, $sanction];
            }
        }

        Cache::$bans[$target] = [($player), time() + $time, $sanction];
        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le joueur §6" . $target . " §fa été banni pendant §6" . $format . "§f par §6" . $player . "§f, raison: §6" . $sanction);

        $embed = new EmbedBuilder();
        $embed->setDescription("**Ban**\n\n**Joueur**\n" . $target . "\n**Temps**\n" . $format . "\n**Raison**\n" . $sanction . "\n\n*Banni par le staff: " . $player . "*");
        $embed->setColor(11141120);
        Discord::send($embed, Cache::$config["sanction-webhook"]);
    }

    private static function kickPlayer(Player $player, string $target, string $sanction): void
    {
        /** @noinspection PhpDeprecationInspection */
        $targetPlayer = Main::getInstance()->getServer()->getPlayerByPrefix($target);

        if (!$targetPlayer instanceof Player) {
            $player->sendMessage(Util::PREFIX . "Le joueur indiqué n'est pas connecté sur le serveur");
            return;
        }

        $targetPlayer->kick("§fVous avez été expulsé de nitrofaction.\n\nPar: §6" . $player->getName() . "\n§fRaison: §6" . $sanction);
        $player->sendMessage(Util::PREFIX . "Vous avez expulsé du serveur §6" . $targetPlayer->getName());

        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le joueur §6" . $targetPlayer->getName() . " §fa été expulsé du serveur par §6" . $player->getName() . "§f, raison: §6" . $sanction);

        $embed = new EmbedBuilder();
        $embed->setDescription("**Kick**\n\n**Joueur**\n" . $targetPlayer->getName() . "\n**Raison**\n" . $sanction . "\n\n*Kick par le staff: " . $player->getName() . "*");
        $embed->setColor(16733525);
        Discord::send($embed, Cache::$config["sanction-webhook"]);
    }

    private static function mutePlayer(Player $player, string $target, string $sanction, int $time): void
    {
        /** @noinspection PhpDeprecationInspection */
        $targetPlayer = Main::getInstance()->getServer()->getPlayerByPrefix($target);

        if (!$targetPlayer instanceof Player) {
            $player->sendMessage(Util::PREFIX . "Le joueur indiqué n'est pas connecté sur le serveur");
            return;
        }

        $format = Util::formatDurationFromSeconds($time);
        $session = Session::get($targetPlayer);

        if ($session->inCooldown("mute") && ($session->getCooldownData("mute")[0] - time()) > $time) {
            $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas mute un joueur moins de temps qu'il est actuellement mute");
            return;
        }

        $session->setCooldown("mute", $time);
        $targetPlayer->sendMessage(Util::PREFIX . "Vous avez été mute pendant §6" . $format . "§f, par §6" . $player->getName() . "§f, raison: §6" . $sanction);

        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le joueur §6" . $targetPlayer->getName() . " §fa été mute pendant §6" . $format . " §fpar §6" . $player->getName() . "§f, raison: §6" . $sanction);

        $embed = new EmbedBuilder();
        $embed->setDescription("**Mute**\n\n**Joueur**\n" . $targetPlayer->getName() . "\n**Temps**\n" . $format . "\n**Raison**\n" . $sanction . "\n\n*Mute par le staff: " . $player->getName() . "*");
        $embed->setColor(16755200);
        Discord::send($embed, Cache::$config["sanction-webhook"]);
    }

    private static function timeForm(Player $player, string $target, string $type, string $sanction): void
    {
        $form = new SimpleForm(function (Player $player, mixed $data) use ($target, $type, $sanction) {
            if (!is_string($data)) {
                return;
            }

            $time = intval($data);
            self::sanctionPlayer($player, $target, $type, $sanction, $time);
        });

        foreach (Cache::$config["sanctions"][$type][$sanction] as $key) {
            $form->addButton(Util::formatDurationFromSeconds($key), -1, "", $key);
        }

        $form->setTitle(ucfirst($type));
        $form->setContent(Util::PREFIX . "Cliquez sur le bouton de votre choix");
        $player->sendForm($form);
    }
}