<?php /** @noinspection PhpUnused */

namespace Kitmap\command\player\rank;

use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use Kitmap\handler\Cache;
use Kitmap\handler\discord\Discord;
use Kitmap\handler\discord\EmbedBuilder;
use Kitmap\handler\Rank;
use Kitmap\handler\ScoreFactory;
use Kitmap\Main;
use Kitmap\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class Nick extends BaseCommand {
	public function __construct(PluginBase $plugin) {
		parent::__construct(
			$plugin,
			"nick",
			"Permet de cacher son vrai pseudo aux yeux des autres joueurs"
		);

		$this->setPermissions([ DefaultPermissions::ROOT_USER ]);
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void {
		if ($sender instanceof Player) {
			if (!Rank::hasRank($sender, "champion")) {
				$sender->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de faire cela");
				return;
			}

			$form = new SimpleForm(function(Player $player, mixed $data) {
				if (!is_int($data)) {
					return;
				}

				switch ($data) {
					case 0:
						$this->customNickForm($player);
						return;
					case 1:
						$usernames = Cache::$config["usernames"];
						$username = $usernames[array_rand($usernames)];

						$this->nickPlayer($player, $username);
						break;
					case 2:
						$player->sendMessage(Util::PREFIX . "Vous avez retrouvé votre pseudo de base !");
						$player->setDisplayName($player->getName());

						$this->sendToWebhook($player);

						Rank::updateNameTag($player);
						ScoreFactory::updateScoreboard($player);
						break;
				}
			});
			$form->setTitle("Nick");
			$form->setContent(Util::PREFIX . "Cliquez sur le bouton de votre choix");
			$form->addButton("Nick custom");
			$form->addButton("Nick aléatoire");
			$form->addButton("Supprimer son nick");
			$sender->sendForm($form);
		}
	}

	private function customNickForm(Player $player) : void {
		$form = new CustomForm(function(Player $player, mixed $data) {
			if (!is_array($data) || !isset($data[0])) {
				return;
			}

			$name = TextFormat::clean($data[0]);

			if (
				2 > strlen($name) ||
				strlen($name) >= 15 ||
				isset(Cache::$players["upper_name"][strtolower($name)]) ||
				preg_match('/[\'^£$%&*()}{@#~?<>,|=_+¬-]/', $name)
			) {
				$player->sendMessage(Util::PREFIX . "Le pseudo indiqué est invalide");
				return;
			}

			$this->nickPlayer($player, $name);
		});
		$form->setTitle("Nick");
		$form->addInput(Util::PREFIX . "Entrez le nick de votre choix");
		$player->sendForm($form);
	}

	private function nickPlayer(Player $player, string $name) : void {
		$player->setDisplayName($name);
		$player->sendMessage(Util::PREFIX . "Vous vous appellez désormais §e" . $name);

		Rank::updateNameTag($player);
		ScoreFactory::updateScoreboard($player);

		Main::getInstance()->getLogger()->info("Le joueur " . $player->getName() . " vient de se nick en " . $name);
		$this->sendToWebhook($player);
	}

	private function sendToWebhook(Player $player) : void {
		$description = match (true) {
			$player->getName() === $player->getDisplayName() => "\n*Supression de son nick*",
			default => "\n*Nouveau Nick: " . $player->getDisplayName() . "*"
		};

		$embed = new EmbedBuilder();
		$embed->setDescription("**Nick**\n\n**Joueur**\n" . $player->getName() . "\n" . $description);
		$embed->setColor(16755200);
		Discord::send($embed, Cache::$config["nick-webhook"]);
	}

	protected function prepare() : void {
	}
}