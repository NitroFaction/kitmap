<?php

namespace NCore\handler;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use NCore\Base;
use NCore\handler\discord\Discord;
use NCore\handler\discord\EmbedBuilder;
use NCore\Session;
use NCore\Util;
use pocketmine\player\Player;

class SanctionAPI
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
        $targetPlayer = Base::getInstance()->getServer()->getPlayerByPrefix($target);
        $format = SanctionAPI::format($time);

        if ($targetPlayer instanceof Player) {
            $data = Session::get($targetPlayer)->data["player"];
            $target = strtolower($targetPlayer->getName());

            $targetPlayer->kick("§fVous êtes banni de nitrofaction.\n\n§fTemps restant: §e" . $format . "\n§fRaison: §e" . $sanction);
            Base::getInstance()->getServer()->getNetwork()->blockAddress($targetPlayer->getNetworkSession()->getIp(), min(300, $time));
        } else {
            $file = Util::getFile("players/" . $target);
            $data = $file->getAll();
        }

        foreach (Cache::$config["saves"] as $column) {
            foreach ($data[$column] ?? [] as $datum) {
                Cache::$bans[$datum] = [$player, time() + $time, $sanction];
            }
        }

        Cache::$bans[$target] = [($player), time() + $time, $sanction];
        Base::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le joueur §e" . $target . " §fa été banni pendant §e" . $format . "§f par §e" . $player . "§f, raison: §e" . $sanction);

        $embed = new EmbedBuilder();
        $embed->setDescription("**Ban**\n\n**Joueur**\n" . $target . "\n**Temps**\n" . $format . "\n**Raison**\n" . $sanction . "\n\n*Banni par le staff: " . $player . "*");
        $embed->setColor(11141120);
        Discord::send($embed, Cache::$config["sanction_webhook"]);
    }

    public static function format(int $seconds, int $type = 0): string
    {
        if ($seconds === -1) {
            return "Permanent";
        }

        $d = floor($seconds / (3600 * 24));
        $h = floor($seconds % (3600 * 24) / 3600);
        $m = floor($seconds % 3600 / 60);
        $s = floor($seconds % 60);

        $dDisplay = $d > 0 ? $d . ($type === 0 ? " jour" . ($d == 1 ? "" : "s") : "j") . ", " : "";
        $hDisplay = $h > 0 ? $h . ($type === 0 ? " heure" . ($h == 1 ? "" : "s") : "h") . ", " : "";
        $mDisplay = $m > 0 ? $m . ($type === 0 ? " minute" . ($m == 1 ? "" : "s") : "m") . ", " : "";
        $sDisplay = $s > 0 ? $s . ($type === 0 ? " seconde" . ($s == 1 ? "" : "s") : "s") . ", " : "";

        $format = rtrim($dDisplay . $hDisplay . $mDisplay . $sDisplay, ", ");

        if (substr_count($format, ",") > 0) {
            return preg_replace("~(.*)" . preg_quote(",", "~") . "~", "$1 et", $format, 1);
        } else {
            return $format;
        }
    }

    private static function kickPlayer(Player $player, string $target, string $sanction): void
    {
        $targetPlayer = Base::getInstance()->getServer()->getPlayerByPrefix($target);

        if (!$targetPlayer instanceof Player) {
            $player->sendMessage(Util::PREFIX . "Le joueur indiqué n'est pas connecté sur le serveur");
            return;
        }

        $targetPlayer->kick("§fVous avez été expulsé de nitrofaction.\n\nPar: §e" . $player->getName() . "\n§fRaison: §e" . $sanction);
        $player->sendMessage(Util::PREFIX . "Vous avez expulsé du serveur §e" . $targetPlayer->getName());

        Base::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le joueur §e" . $targetPlayer->getName() . " §fa été expulsé du serveur par §e" . $player->getName() . "§f, raison: §e" . $sanction);

        $embed = new EmbedBuilder();
        $embed->setDescription("**Kick**\n\n**Joueur**\n" . $targetPlayer->getName() . "\n**Raison**\n" . $sanction . "\n\n*Kick par le staff: " . $player->getName() . "*");
        $embed->setColor(16733525);
        Discord::send($embed, Cache::$config["sanction_webhook"]);
    }

    private static function mutePlayer(Player $player, string $target, string $sanction, int $time): void
    {
        $targetPlayer = Base::getInstance()->getServer()->getPlayerByPrefix($target);

        if (!$targetPlayer instanceof Player) {
            $player->sendMessage(Util::PREFIX . "Le joueur indiqué n'est pas connecté sur le serveur");
            return;
        }

        $format = SanctionAPI::format($time);
        $session = Session::get($targetPlayer);

        if ($session->inCooldown("mute") && ($session->getCooldownData("mute")[0] - time()) > $time) {
            $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas mute un joueur moins de temps qu'il est actuellement mute");
            return;
        }

        $session->setCooldown("mute", $time);
        $targetPlayer->sendMessage(Util::PREFIX . "Vous avez été mute pendant §e" . $format . "§f, par §e" . $player->getName() . "§f, raison: §e" . $sanction);

        Base::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le joueur §e" . $targetPlayer->getName() . " §fa été mute pendant §e" . $format . " §fpar §e" . $player->getName() . "§f, raison: §e" . $sanction);

        $embed = new EmbedBuilder();
        $embed->setDescription("**Mute**\n\n**Joueur**\n" . $targetPlayer->getName() . "\n**Temps**\n" . $format . "\n**Raison**\n" . $sanction . "\n\n*Mute par le staff: " . $player->getName() . "*");
        $embed->setColor(16755200);
        Discord::send($embed, Cache::$config["sanction_webhook"]);
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
            $form->addButton(self::format($key), -1, "", $key);
        }

        $form->setTitle(ucfirst($type));
        $form->setContent(Util::PREFIX . "Cliquez sur le bouton de votre choix");
        $player->sendForm($form);
    }
}