<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\BaseCommand;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\item\VanillaItems;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class XpBottle extends BaseCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "xpbottle",
            "Transforme ses niveaux en une seul bouteille d'expérience"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $amount = $args["montant"] ?? $sender->getXpManager()->getXpLevel();
            $amount = intval($amount);

            if ($amount < 1 || $amount > 1000) {
                $sender->sendMessage(Util::PREFIX . "Le montant indiqué est invalide");
                return;
            } else if ($amount > $sender->getXpManager()->getXpLevel()) {
                $sender->sendMessage(Util::PREFIX . "Vous n'avez pas assez de niveaux");
                return;
            }

            $item = VanillaItems::EXPERIENCE_BOTTLE();
            $item->getNamedTag()->setInt("xp_bottle", $amount);
            $item->setCustomName("§r§fBouteille d'expérience §6(" . $amount . ")");

            Util::addItem($sender, $item);

            $sender->getXpManager()->setXpLevel($sender->getXpManager()->getXpLevel() - $amount);
            $sender->sendMessage(Util::PREFIX . "Vous avez crée une bouteille d'expérience avec §6" . $amount . " niveaux §fà l'intérieur");
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new IntegerArgument("montant", true));
    }
}