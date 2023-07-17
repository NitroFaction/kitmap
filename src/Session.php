<?php

namespace NCore;

use NCore\command\player\util\faction\CoinFlip;
use NCore\handler\Cache;
use NCore\task\repeat\BaseTask;
use pocketmine\player\Player;
use WeakMap;

class Session
{
    /** @phpstan-var WeakMap<Player, Session> */
    private static WeakMap $sessions;

    public function __construct(private Player $player, public array $data)
    {
    }

    public static function get(Player $player): Session
    {
        self::$sessions ??= new WeakMap();
        return self::$sessions[$player] ??= self::loadSessionData($player);
    }

    private static function loadSessionData(Player $player): Session
    {
        $username = strtolower($player->getName());

        $file = Util::getFile("players/" . $username);
        $data = $file->getAll();

        if ($data === []) {
            $ownings = Util::getFile("ownings");
            $ownings = $ownings->get($player->getXuid());

            $data = array_merge(Cache::$config["default_data"], [
                "rank" => $ownings["rank"] ?? "joueur",
                "cosmetics" => $ownings["cosmetics"] ?? [],
                "tags" => $ownings["tags"] ?? []
            ]);
        }

        $data += [
            "reply" => null,
            "plot" => [false, null, null, false],
            "last_hit" => [null, time()],
            "invite" => [],
            "upper_name" => $player->getName(),
            "xuid" => $player->getXuid(),
            "ping" => []
        ];

        [$ip, $uuid] = [
            $player->getNetworkSession()->getIp(),
            $player->getNetworkSession()->getPlayerInfo()->getUuid()->toString()
        ];

        [$did, $ssi, $cid] = array_values(array_intersect_key(
            $player->getPlayerInfo()->getExtraData(),
            array_flip(["DeviceId", "SelfSignedId", "ClientRandomId"])
        ));

        $vars = array_filter(get_defined_vars(), fn($value) => is_int($value) || is_string($value));

        foreach ([$ip, $uuid, $did, $ssi, $cid] as $value) {
            if (in_array($value, array_values($vars)) && !in_array($value, $data[($column = array_search($value, $vars))])) {
                $data[$column][] = $value;
            }
        }

        foreach ($data as $key => $value) {
            if (isset(Cache::$players[$key])) {
                Cache::$players[$key][$username] = $value;
            }
        }

        return new Session($player, [
            "claim" => [false, "Nature", 0],
            "play_time" => time(),
            "skin" => $player->getSkin(),
            "player" => $data
        ]);
    }

    public function saveSessionData(bool $quit = true): void
    {
        $player = $this->player;
        $username = strtolower($player->getName());

        if ($quit) {
            $this->removeCooldown("enderpearl");

            foreach (CoinFlip::$coinflip as $id => $value) {
                if ($value["username"] === $username) {
                    $this->addValue("money", $value["price"]);
                    unset(CoinFlip::$coinflip[$id]);
                }
            }
        }

        $this->data["player"]["played_time"] += time() - $this->data["play_time"];
        $this->data["play_time"] = time();

        Cache::$players["played_time"][$username] = $this->data["player"]["played_time"];

        $data = $this->data["player"];
        $file = Util::getFile("players/" . $username);

        $file->setAll($data);
        $file->save();
    }

    public function removeCooldown(string $key): void
    {
        unset($this->data["player"]["cooldown"][$key]);
    }

    public function addValue(string $key, int $value, bool $substraction = false): void
    {
        $this->data["player"][$key] = ($substraction ? $this->data["player"][$key] - $value : $this->data["player"][$key] + $value);

        if (isset(Cache::$players[$key])) {
            $username = strtolower($this->player->getName());
            Cache::$players[$key][$username] = $this->data["player"][$key];
        }
    }

    public function setCooldown(string $key, int $time, array $value = []): void
    {
        if ($key === "combat" && $this->player->isCreative()) {
            return;
        } else if ($key === "combat" && !self::inCooldown("combat")) {
            $this->player->sendMessage(Util::PREFIX . "Vous êtes désormais en combat, vous ne pouvez plus vous téléporter ou vous déconnecter !");
            BaseTask::$combat[] = $this->player->getName();
        }

        $this->data["player"]["cooldown"][$key] = array_merge([time() + $time], $value);
    }

    public function inCooldown(string $key): bool
    {
        if ($key === "combat" && $this->player->isCreative()) {
            return false;
        } else {
            return isset($this->data["player"]["cooldown"][$key]) && $this->data["player"]["cooldown"][$key][0] > time();
        }
    }

    public function getCooldownData(string $key): array
    {
        return $this->data["player"]["cooldown"][$key] ?? [time(), null, null, null];
    }
}