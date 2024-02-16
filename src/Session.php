<?php

namespace Kitmap;

use Kitmap\command\player\CoinFlip;
use Kitmap\command\player\Gambling;
use Kitmap\handler\Cache;
use pocketmine\player\Player;
use WeakMap;

class Session
{
    /** @phpstan-var WeakMap<Player, Session> */
    private static WeakMap $sessions;

    public function __construct(private readonly Player $player, public array $data)
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

        $file = Util::getFile("data/players/" . $username);
        $data = $file->getAll();

        if ($data === []) {
            $ownings = Util::getFile("ownings");
            $rank = $ownings->get(strtolower($player->getName()), "joueur");

            $data = array_merge(Cache::$config["default-data"], [
                "rank" => $rank
            ]);
        }

        $data += [
            "reply" => null,
            "last_hit" => [null, time()],
            "invite" => [],
            "upper_name" => $player->getName(),
            "xuid" => $player->getXuid()
        ];

        $data["connection"] = time();

        [$ip, $uuid] = [
            $player->getNetworkSession()->getIp(),
            $player->getNetworkSession()->getPlayerInfo()->getUuid()->toString()
        ];

        [$did] = array_values(array_intersect_key(
            $player->getPlayerInfo()->getExtraData(),
            array_flip(["DeviceId"])
        ));

        $vars = array_filter(get_defined_vars(), fn($value) => is_int($value) || is_string($value));

        foreach ([$ip, $uuid, $did] as $value) {
            if (in_array($value, array_values($vars)) && !in_array($value, $data[($column = array_search($value, $vars))])) {
                $data[$column][] = $value;
            }
        }

        foreach ($data as $key => $value) {
            if (isset(Cache::$players[$key])) {
                Cache::$players[$key][$username] = $value;
            }
        }

        return new Session($player, $data);
    }

    public function saveSessionData(bool $destroy = true): void
    {
        $player = $this->player;
        $username = strtolower($player->getName());

        $this->data["played_time"] += time() - $this->data["connection"];
        $this->data["connection"] = time();

        Cache::$players["played_time"][$username] = $this->data["played_time"];

        if ($destroy) {
            $this->removeCooldown("enderpearl");

            foreach (CoinFlip::$coinflip as $id => $value) {
                if ($value["username"] === $username) {
                    $this->addValue("money", $value["price"]);
                    unset(CoinFlip::$coinflip[$id]);
                }
            }

            if (isset(Gambling::$gamblings[$username])) {
                $data = Gambling::$gamblings[$username];

                $this->removeCooldown("gambling");
                $this->addValue("money", $data["bet"]);

                unset(Gambling::$gamblings[$username]);
            }

            unset(self::$sessions[$player]);
        }

        $data = $this->data;
        $file = Util::getFile("data/players/" . $username);

        $file->setAll($data);
        $file->save();
    }

    public function removeCooldown(string $key): void
    {
        unset($this->data["cooldown"][$key]);
    }

    public function addValue(array|string $path, int|float $value = 1, bool $substraction = false): void
    {
        $this->data = Util::addArrayValue($this->data, $path, intval($value), $substraction);

        if ($path === "bounty") {
            Util::updateBounty($this->player);
        }

        if (is_string($path) && isset(Cache::$players[$path])) {
            $username = strtolower($this->player->getName());
            Cache::$players[$path][$username] = $this->data[$path];
        }
    }

    public function setCooldown(string $key, float|int $time, array $value = []): void
    {
        if ($key === "combat" && $this->player->isCreative()) {
            return;
        } else if ($key === "combat" && !self::inCooldown("combat")) {
            $this->player->sendMessage(Util::PREFIX . "Vous êtes désormais en combat, vous ne pouvez plus vous téléporter ou vous déconnecter !");
            Cache::$combatPlayers[$this->player] = true;
        }

        $this->data["cooldown"][$key] = array_merge([time() + $time], $value);
    }

    public function inCooldown(string $key): bool
    {
        if ($key === "combat" && $this->player->isCreative()) {
            return false;
        } else {
            return isset($this->data["cooldown"][$key]) && $this->data["cooldown"][$key][0] > time();
        }
    }

    public function getCooldownData(string $key): array
    {
        return $this->data["cooldown"][$key] ?? [time(), null, null, null];
    }
}
