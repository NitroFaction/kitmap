<?php /** @noinspection PhpUnused */

namespace Kitmap\command\staff\op;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TargetArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\handler\Cache;
use Kitmap\Main;
use Kitmap\Session;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class TransferAccount extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "transferaccount",
            "Transfère les données d'un compte à un autre"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $old = strtolower($args["ancien"]);
        $new = strtolower($args["nouveau"]);

        /** @noinspection PhpDeprecationInspection */
        $oldPlayer = Main::getInstance()->getServer()->getPlayerByPrefix($old);

        /** @noinspection PhpDeprecationInspection */
        $newPlayer = Main::getInstance()->getServer()->getPlayerByPrefix($new);

        $file = Util::getFile("ownings");

        $newData = $this->getData($new);
        $oldData = $this->getData($old);

        if ($oldPlayer instanceof Player) {
            $oldData = Session::get($oldPlayer)->data;
        } else {
            if (is_bool($oldData)) {
                $sender->sendMessage(Util::PREFIX . "L'ancien pseudo ne s'est jamais connecté au serveur");
                return;
            }
        }

        if ($newPlayer instanceof Player) {
            $newData = Session::get($newPlayer)->data;
        } else {
            if (is_bool($newData)) {
                $sender->sendMessage(Util::PREFIX . "Le nouveau pseudo ne s'est jamais connecté au serveur");
                return;
            }
        }

        $this->setData($old, $newData);
        $this->setData($new, $oldData);

        $file->set($oldData["xuid"], $file->get($newData["xuid"]));
        $file->set($newData["xuid"], $file->get($oldData["xuid"]));

        $file->save();
        $sender->sendMessage(Util::PREFIX . "Vous venez de transférer les données de §e" . $old . " §fvers §e" . $new);
    }

    public function getData(string $username): array|bool
    {
        if (!isset(Cache::$players["upper_name"][$username])) {
            return false;
        }

        $file = Util::getFile("data/players/" . $username);
        return $file->getAll();
    }

    public function setData(string $username, array $data): void
    {
        if (($player = Main::getInstance()->getServer()->getPlayerExact($username)) instanceof Player) {
            Session::get($player)->data = $data;
            return;
        }

        $file = Util::getFile("data/players/" . $username);

        $file->setAll($data);
        $file->save();
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("ancien"));
        $this->registerArgument(0, new TargetArgument("ancien"));
        $this->registerArgument(1, new RawStringArgument("nouveau"));
        $this->registerArgument(1, new TargetArgument("nouveau"));
    }
}