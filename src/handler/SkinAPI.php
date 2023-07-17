<?php

namespace NCore\handler;

use NCore\Base;
use NCore\Session;
use pocketmine\entity\Skin;
use pocketmine\player\Player;
use Ramsey\Uuid\Nonstandard\Uuid;

class SkinAPI
{
    public static array $skins;

    public static function setDefaultSkin(Player $player): void
    {
        $session = Session::get($player);

        $skin = $session->data["skin"];
        $cosmetic = $session->data["player"]["cosmetic"];

        if (!is_null($cosmetic[0])) {
            $skin = self::getCosmetic($skin, $cosmetic[0], $cosmetic[1]);
        }

        if ($player->getSkin() !== $skin) {
            $player->setSkin($skin);
            $player->sendSkin();
        }
    }

    public static function getCosmetic(Skin $skin, string $name, string $texture = "default"): Skin
    {
        $geometryData = self::$skins[$name]["geometry"];
        $skinData = self::combineSkin($skin->getSkinData(), self::$skins[$name]["texture"][$texture]);

        return new Skin($skin->getSkinId(), $skinData, "", "geometry." . $name, $geometryData);
    }

    public static function combineSkin(string $skinBytes, string $cosmeticBytes): string
    {
        $bytes = "";

        for ($i = 0; $i < 16384; $i += 256) {
            $bytes .= substr($skinBytes, $i, 256) . substr($cosmeticBytes, ($i * 2) + 256, 256);
        }
        return $bytes . substr($cosmeticBytes, 32768, 32768);
    }

    public static function checkSkin(Player $player, Skin $skin = null): Skin
    {
        $session = Session::get($player);
        $cosmetic = $session->data["player"]["cosmetic"];

        $skin = $skin ?? $player->getSkin();
        $old = clone($skin);

        if (strlen($skin->getSkinData()) !== 64 * 64 * 4) {
            $skin = self::getSkinFromName("steve");
        }

        self::saveSkin($player, $skin);

        if (!is_null($cosmetic[0])) {
            $skin = self::getCosmetic($skin, $cosmetic[0], $cosmetic[1]);
        }

        if ($old !== $skin) {
            $player->setSkin($skin);
            $player->sendSkin();
        }
        return $skin;
    }

    public static function getSkinFromName(string $name): Skin
    {
        if (isset(self::$skins[$name])) {
            if (isset(self::$skins[$name]["geometry"])) {
                return new Skin(Uuid::uuid4()->toString(), self::$skins[$name]["texture"]["default"], "", "geometry." . $name, self::$skins[$name]["geometry"]);
            } else {
                return new Skin(Uuid::uuid4()->toString(), self::$skins[$name]["texture"]["default"], "", "geometry.humanoid.custom", "");
            }
        } else {
            $path = Base::getInstance()->getDataFolder() . "data/skins/" . $name . ".png";

            if (!file_exists($path)) {
                return self::getSkinFromName("steve");
            } else {
                return new Skin(Uuid::uuid4()->toString(), self::getBytesFromImage($path));
            }
        }
    }

    public static function getBytesFromImage(string $path): string
    {
        $bytes = "";

        $image = imagecreatefrompng($path);
        $size = @getimagesize($path);

        for ($y = 0; $y < $size[1]; $y++) {
            for ($x = 0; $x < $size[0]; $x++) {
                $rgba = @imagecolorat($image, $x, $y);
                $a = ((~(($rgba >> 24))) << 1) & 0xff;
                $r = ($rgba >> 16) & 0xff;
                $g = ($rgba >> 8) & 0xff;
                $b = $rgba & 0xff;
                $bytes .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }

        @imagedestroy($image);
        return $bytes;
    }

    private static function saveSkin(Player $player, Skin $skin): void
    {
        $session = Session::get($player);
        $session->data["skin"] = $skin;

        $path = Base::getInstance()->getDataFolder() . "data/skins/" . strtolower($player->getName()) . ".png";
        $img = imagecreatetruecolor(64, 64);

        imagealphablending($img, false);
        imagesavealpha($img, true);

        $index = 0;

        for ($y = 0; $y < 64; ++$y) {
            for ($x = 0; $x < 64; ++$x) {
                $list = substr($skin->getSkinData(), $index, 4);

                $r = ord($list[0]);
                $g = ord($list[1]);
                $b = ord($list[2]);

                $a = 127 - (ord($list[3]) >> 1);
                $index += 4;

                $color = imagecolorallocatealpha($img, $r, $g, $b, $a);
                imagesetpixel($img, $x, $y, $color);
            }
        }

        imagepng($img, $path);
        imagedestroy($img);
    }

    public static function setCosmetic(Player $player, string $name, string $texture = "default"): void
    {
        $session = Session::get($player);
        $skin = $session->data["skin"];

        if (!$skin instanceof Skin) {
            return;
        }

        $skin = self::getCosmetic($skin, $name, $texture);

        $player->setSkin($skin);
        $player->sendSkin();
    }
}