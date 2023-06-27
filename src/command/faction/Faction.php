<?php /** @noinspection PhpUnused */

namespace Kitmap\command\faction;

use CortexPE\Commando\BaseCommand;
use Kitmap\command\faction\subcommands\Accept;
use Kitmap\command\faction\subcommands\Admin;
use Kitmap\command\faction\subcommands\Chat;
use Kitmap\command\faction\subcommands\Claim;
use Kitmap\command\faction\subcommands\Create;
use Kitmap\command\faction\subcommands\Delete;
use Kitmap\command\faction\subcommands\Delhome;
use Kitmap\command\faction\subcommands\Demote;
use Kitmap\command\faction\subcommands\Home;
use Kitmap\command\faction\subcommands\Info;
use Kitmap\command\faction\subcommands\Invite;
use Kitmap\command\faction\subcommands\Kick;
use Kitmap\command\faction\subcommands\Leader;
use Kitmap\command\faction\subcommands\Leave;
use Kitmap\command\faction\subcommands\Logs;
use Kitmap\command\faction\subcommands\Permissions;
use Kitmap\command\faction\subcommands\Promote;
use Kitmap\command\faction\subcommands\Rename;
use Kitmap\command\faction\subcommands\Sethome;
use Kitmap\command\faction\subcommands\Top;
use Kitmap\command\faction\subcommands\Unclaim;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;

class Faction extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct($plugin, "faction", "Les commandes relatant au faction");
        $this->setAliases(["f"]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
    }

    protected function prepare(): void
    {
        $this->registerSubCommand(new Chat());
        $this->registerSubCommand(new Claim());
        $this->registerSubCommand(new Accept());
        $this->registerSubCommand(new Create());
        $this->registerSubCommand(new Delete());
        $this->registerSubCommand(new Delhome());
        $this->registerSubCommand(new Demote());
        $this->registerSubCommand(new Home());
        $this->registerSubCommand(new Admin());
        $this->registerSubCommand(new Info());
        $this->registerSubCommand(new Rename());
        $this->registerSubCommand(new Invite());
        $this->registerSubCommand(new Kick());
        $this->registerSubCommand(new Leader());
        $this->registerSubCommand(new Leave());
        $this->registerSubCommand(new Logs());
        $this->registerSubCommand(new Permissions());
        $this->registerSubCommand(new Promote());
        $this->registerSubCommand(new Sethome());
        $this->registerSubCommand(new Top());
        $this->registerSubCommand(new Unclaim());
    }
}