<?php

namespace NCore\command\sub\faction\admin\plots;

use CortexPE\Commando\BaseSubCommand;
use NCore\Base;
use NCore\handler\OtherAPI;
use NCore\Session;
use NCore\Util;
use pocketmine\command\CommandSender;
use pocketmine\item\ItemFactory;
use pocketmine\player\Player;

class Create extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Base::getInstance(), "create", "");
        $this->setPermission("pocketmine.group.operator");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if (isset($session->data["player"]["plot"]) && $session->data["player"]["plot"][0]) {
                $sender->sendMessage(Util::PREFIX . "Vous avez déjà recu une houe pour délimiter un plot");
                return;
            }

            $item = ItemFactory::getInstance()->get(294);
            $item->setCustomName("§r§ePlots");

            $item->getNamedTag()->setString("plothoe", "until");
            OtherAPI::addItem($sender, $item);

            $sender->sendMessage(Util::PREFIX . "Vous venez de recevoir une houe pour créer un plot\n\n§e§l§l» §r§r§fPour definir la première position: §eClique droit\n§e§l§l» §r§r§fPour définir la deuxième position: §eCasser un bloc\n\n§e§l§l» §r§r§fPour confirmer: §eSneak + Clique Gauche/Droit\n§e§l§l» §r§r§fPour annuler: §e/f plots cancel");
            $session->data["player"]["plot"] = [true, null, null];
        }
    }

    protected function prepare(): void
    {
    }
}