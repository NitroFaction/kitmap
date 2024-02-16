<?php

namespace Kitmap\item;

use Kitmap\handler\Cache;
use Kitmap\Main;
use pocketmine\crafting\ExactRecipeIngredient;
use pocketmine\crafting\ShapedRecipe;
use pocketmine\crafting\ShapelessRecipe;
use pocketmine\item\StringToItemParser;
use ReflectionClass;
use ReflectionProperty;

class Craft
{
    /** @noinspection PhpExpressionResultUnusedInspection */
    public function __construct()
    {
        $remove = Cache::$config["crafts"]["remove"];
        $add = Cache::$config["crafts"]["add"];

        $craftMgr = Main::getInstance()->getServer()->getCraftingManager();
        $reflectionClass = new ReflectionClass($craftMgr);

        $recipes = $craftMgr->getCraftingRecipeIndex();
        $newRecipes = [];

        foreach ($recipes as $recipe) {
            $valid = true;

            if ($recipe instanceof ShapedRecipe || $recipe instanceof ShapelessRecipe) {
                foreach ($recipe->getResults() as $item) {
                    foreach ($remove as $itemName) {
                        $itemToDelete = StringToItemParser::getInstance()->parse($itemName);

                        if ($item->equals($itemToDelete)) {
                            $valid = false;
                        }
                    }
                }
            }

            if ($valid) {
                $newRecipes[] = $recipe;
            }
        }

        foreach ($reflectionClass->getProperties(ReflectionProperty::IS_PRIVATE) as $property) {
            if ($property->getName() === "craftingRecipeIndex") {
                $property->setAccessible(true);
                $property->setValue($craftMgr, $newRecipes);
                $property->setAccessible(false);
            }
        }

        foreach ($add as $value) {
            $input = array_map(function (string $data) {
                $item = StringToItemParser::getInstance()->parse(explode(":", $data)[0]);
                return new ExactRecipeIngredient($item);
            }, $value["input"]);

            $split = explode(":", $value["output"]);
            $result = StringToItemParser::getInstance()->parse($split[0])->setCount($split[1] ?? 1);

            $maxLength = max(array_map("strlen", $value["shape"]));

            foreach ($value["shape"] as $key => $line) {
                $length = strlen($line);

                if ($maxLength > strlen($length)) {
                    $value["shape"][$key] = $line . str_repeat(" ", $maxLength - $length);
                }
            }

            $craftMgr->registerShapedRecipe(new ShapedRecipe(
                $value["shape"],
                $input,
                [$result]
            ));
        }
    }
}