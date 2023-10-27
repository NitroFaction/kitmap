<?php

namespace Kitmap\task;

use Kitmap\handler\Casino;
use pocketmine\block\Block;
use pocketmine\block\Concrete;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\Inventory;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\world\sound\BellRingSound;
use pocketmine\world\sound\RedstonePowerOnSound;

class RouletteTask extends Task
{
    private string $name;

    private Player $player;
    private Inventory $inventory;

    private int $bet;
    private array $roulette;

    private array $colors = [];
    private int $time;

    private int $cooldown = 0;
    private int $close = 40;

    private bool $started = false;
    private ?Block $result = null;

    public function __construct(Player $player, Inventory $inventory, int $bet, array $roulette)
    {
        $this->name = strtolower($player->getName());

        $this->player = $player;
        $this->inventory = $inventory;

        $this->bet = $bet;
        $this->roulette = $roulette;

        $this->time = mt_rand(60, 80);
        $this->update(true);
    }

    private function update(bool $initial = false): void
    {
        $initial ? $this->buildInitialLines() : $this->buildLines();
        $this->generateBorderLines();
    }

    private function buildInitialLines(): void
    {
        reset($this->roulette);
        $randomIndex = mt_rand(0, 36);

        for ($i = 0; $i <= $randomIndex; $i++) {
            next($this->roulette);
        }
        $this->buildLines();
    }

    private function buildLines(): void
    {
        $i = 0;

        $lines = range(9, 17);
        $itemIndex = $this->nextItemIndex();

        foreach ($lines as $line) {
            $regulatedIndex = ($itemIndex + $i) % 37;
            $item = $this->roulette[$regulatedIndex];

            $this->inventory->setItem($line, $item);
            $i++;
        }
    }

    private function nextItemIndex(): int
    {
        $nextIndex = next($this->roulette);

        if ($nextIndex === false) {
            reset($this->roulette);
        }
        return key($this->roulette);
    }

    private function generateBorderLines(): void
    {
        $i = 0;

        if (!$this->started) {
            $this->colors = [DyeColor::RED(), DyeColor::ORANGE(), DyeColor::YELLOW(), DyeColor::LIME(), DyeColor::LIGHT_BLUE(), DyeColor::BLUE(), DyeColor::PURPLE(), DyeColor::PINK(), DyeColor::WHITE()];
            $randomColorIndex = mt_rand(0, 8);

            for ($j = 0; $j <= $randomColorIndex; $j++) {
                next($this->colors);
            }
        }

        $colorIndex = $this->nextColorIndex();

        foreach (range(0, 8) as $slot) {
            $regulatedIndex = ($colorIndex + $i) % 9;
            $color = $this->colors[$regulatedIndex];

            $this->inventory->setItem($slot, VanillaBlocks::STAINED_GLASS()->setColor($color)->asItem());
            $this->inventory->setItem($slot + 18, VanillaBlocks::STAINED_GLASS()->setColor($color)->asItem());

            $i++;
        }
    }

    private function nextColorIndex(): int
    {
        if (next($this->colors) === false) {
            reset($this->colors);
        }
        return key($this->colors);
    }

    public function onRun(): void
    {
        if (!$this->player->isConnected()) {
            $this->getHandler()?->cancel();
            return;
        } else if (is_null($this->player->getCurrentWindow())) {
            $this->close = 0;
            $this->time = 0;
        }

        if ($this->time > 0) {
            if (!$this->started) {
                $this->started = true;
            }
            if ($this->cooldown === 0) {
                $this->player->broadcastSound(new RedstonePowerOnSound(), [$this->player]);
                $this->update();
            }
            if ($this->time <= 20 && $this->cooldown === 0) {
                $this->cooldown = 4;
            }
            if ($this->cooldown > 0) {
                $this->cooldown--;
            }

            $this->time--;
        } else {
            $this->doEndAnimation();

            $resultColor = $this->getResultColor();
            $this->inventory->setItem(13, $this->result->asItem());

            if ($this->close === 40) {
                $this->player->broadcastSound(new BellRingSound(), [$this->player]);
                $this->update();
            }

            if ($this->close <= 0) {
                $resultColorIds = [
                    DyeColor::RED()->name() => 0,
                    DyeColor::BLACK()->name() => 1,
                    DyeColor::LIME()->name() => 2
                ];

                $resultColorId = $resultColorIds[$resultColor->name()];

                if (intval(Casino::$games[$this->name]["color"]) === $resultColorId) {
                    $gain = $resultColorId === 2 ? $this->bet * 14 : $this->bet * 2;
                    Casino::winGame($this->player, "roulette", $gain);
                } else {
                    Casino::loseGame($this->player, "roulette");
                }

                $this->getHandler()?->cancel();
            } else {
                $this->close--;
            }
        }
    }

    private function doEndAnimation(): void
    {
        $colors = [DyeColor::BLACK(), DyeColor::GRAY()];

        $order = match (true) {
            $this->close % 2 === 0 => [0, 1],
            default => [1, 0]
        };

        foreach ($this->inventory->getContents(true) as $slot => $item) {
            if ($slot !== 13) {
                $color = $slot % 2 === 0 ? 0 : 1;
                $this->inventory->setItem($slot, VanillaBlocks::STAINED_GLASS()->setColor($colors[$order[$color]])->asItem());
            }
        }
    }

    private function getResultColor(): DyeColor
    {
        if ($this->result instanceof Concrete) {
            return $this->result->getColor();
        }

        $rand = mt_rand(0, 36);

        if ($rand == 0) {
            $block = VanillaBlocks::CONCRETE()->setColor(DyeColor::LIME());
        } else if ($rand % 2 == 0) {
            $block = VanillaBlocks::CONCRETE()->setColor(DyeColor::RED());
        } else {
            $block = VanillaBlocks::CONCRETE()->setColor(DyeColor::BLACK());
        }

        $this->result = $block;
        return $block->getColor();
    }

    public function onCancel(): void
    {
        if (!$this->player->isConnected()) {
            goto cancel;
        }

        if (array_key_exists($this->name, Casino::$games)) {
            unset(Casino::$games[$this->name]);
        }

        $this->player->removeCurrentWindow();

        cancel:
        parent::onCancel();
    }
}
