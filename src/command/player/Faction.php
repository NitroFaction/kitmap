<?php /** @noinspection PhpUnused */

namespace NCore\command\player;

use CortexPE\Commando\BaseCommand;
use NCore\command\sub\faction\Accept;
use NCore\command\sub\faction\Admin;
use NCore\command\sub\faction\Chat;
use NCore\command\sub\faction\Claim;
use NCore\command\sub\faction\Create;
use NCore\command\sub\faction\Delete;
use NCore\command\sub\faction\Delhome;
use NCore\command\sub\faction\Demote;
use NCore\command\sub\faction\Deposit;
use NCore\command\sub\faction\Home;
use NCore\command\sub\faction\Info;
use NCore\command\sub\faction\Invite;
use NCore\command\sub\faction\Island;
use NCore\command\sub\faction\Kick;
use NCore\command\sub\faction\Leader;
use NCore\command\sub\faction\Leave;
use NCore\command\sub\faction\Logs;
use NCore\command\sub\faction\Permissions;
use NCore\command\sub\faction\Promote;
use NCore\command\sub\faction\Rename;
use NCore\command\sub\faction\Sethome;
use NCore\command\sub\faction\Top;
use NCore\command\sub\faction\Unclaim;
use NCore\command\sub\faction\Withdraw;
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
        $this->registerSubCommand(new Deposit());
        $this->registerSubCommand(new Home());
        $this->registerSubCommand(new Admin());
        $this->registerSubCommand(new Info());
        $this->registerSubCommand(new Rename());
        $this->registerSubCommand(new Invite());
        $this->registerSubCommand(new Island());
        $this->registerSubCommand(new Kick());
        $this->registerSubCommand(new Leader());
        $this->registerSubCommand(new Leave());
        $this->registerSubCommand(new Logs());
        $this->registerSubCommand(new Permissions());
        $this->registerSubCommand(new Promote());
        $this->registerSubCommand(new Sethome());
        $this->registerSubCommand(new Top());
        $this->registerSubCommand(new Unclaim());
        $this->registerSubCommand(new Withdraw());
    }
}