<?php

namespace Kitmap\command;

use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\BaseSubCommand;
use Kitmap\handler\Cache;
use Kitmap\Main;
use ReflectionClass;

class Commands
{
    public function __construct()
    {
        $plugin = Main::getInstance();

        foreach (Cache::$config["commands"] as $command) {
            $commandMap = $plugin->getServer()->getCommandMap();
            $cmd = $commandMap->getCommand($command);

            if ($cmd != null) {
                $commandMap->unregister($cmd);
            }
        }

        $this->callDirectory("command", function (string $namespace) use ($plugin): void {
            $instanceNamespace = BaseCommand::class;
            $factionNamespace = BaseSubCommand::class;

            if (class_exists($instanceNamespace) && is_subclass_of($namespace, $instanceNamespace) && !is_subclass_of($namespace, $factionNamespace)) {
                $plugin->getServer()->getCommandMap()->register("Kitmap", new $namespace($plugin));
            }
        });
    }

    private function callDirectory(string $directory, callable $callable): void
    {
        $main = explode("\\", Main::getInstance()->getDescription()->getMain());
        unset($main[array_key_last($main)]);

        $main = implode("/", $main);
        $directory = rtrim(str_replace(DIRECTORY_SEPARATOR, "/", $directory), "/");

        $reflectionClass = new ReflectionClass(Main::class);
        $reflectionMethod = $reflectionClass->getMethod("getFile");
        /** @noinspection PhpExpressionResultUnusedInspection */
        $reflectionMethod->setAccessible(true);
        $resp = $reflectionMethod->invoke(Main::getInstance());

        $dir = $resp . "src/" . $directory;

        foreach (array_diff(scandir($dir), [".", ".."]) as $file) {
            $path = $dir . "/" . $file;
            $extension = pathinfo($path)["extension"] ?? null;

            if ($extension === null) {
                $this->callDirectory($directory . "/" . $file, $callable);
            } else if ($extension === "php") {
                $namespaceDirectory = str_replace("/", "\\", $directory);
                $namespaceMain = str_replace("/", "\\", $main);

                $namespace = $namespaceMain . "\\$namespaceDirectory\\" . basename($file, ".php");
                $callable($namespace);
            }
        }
    }
}