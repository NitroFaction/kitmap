<?php

namespace NCore\command;

use NCore\Base;
use NCore\handler\Cache;

class CommandManager
{
    public static function startup(): void
    {
        foreach (Cache::$config["commands"] as $command) {
            $commandMap = Base::getInstance()->getServer()->getCommandMap();
            $cmd = $commandMap->getCommand($command);

            if ($cmd != null) {
                $commandMap->unregister($cmd);
            }
        }

        self::loadCommand("command/player");
        self::loadCommand("command/staff");
    }

    private static function loadCommand(string $path): void
    {
        self::callDirectory($path, function (string $namespace): void {
            if (!isset(class_implements($namespace)[NonAutomaticCallItemTrait::class])) {
                Base::getInstance()->getServer()->getCommandMap()->register("Nitro", new $namespace(Base::getInstance()));
            }
        });
    }

    private static function callDirectory(string $directory, callable $callable): void
    {
        $main = explode("\\", Base::getInstance()->getDescription()->getMain());
        unset($main[array_key_last($main)]);

        $main = implode("/", $main);
        $directory = rtrim(str_replace(DIRECTORY_SEPARATOR, "/", $directory), "/");

        $dir = Base::getInstance()->getFile() . "src/" . $directory;

        foreach (array_diff(scandir($dir), [".", ".."]) as $file) {
            $path = $dir . "/" . $file;
            $extension = pathinfo($path)["extension"] ?? null;

            if ($extension === null) {
                self::callDirectory($directory . "/" . $file, $callable);
            } else if ($extension === "php") {
                $namespaceDirectory = str_replace("/", "\\", $directory);
                $namespaceMain = str_replace("/", "\\", $main);

                $namespace = $namespaceMain . "\\$namespaceDirectory\\" . basename($file, ".php");
                $callable($namespace);
            }
        }
    }
}