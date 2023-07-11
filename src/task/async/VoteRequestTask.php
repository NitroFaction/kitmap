<?php

namespace Kitmap\task\async;

use Kitmap\command\player\Vote;
use pocketmine\scheduler\AsyncTask;

class VoteRequestTask extends AsyncTask {
	private ?int $return = null;

	public function __construct(private readonly string $player, private readonly string $type, private readonly string $url) {
	}

	public function onRun() : void {
		$query = curl_init($this->url);
		curl_setopt($query, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($query, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($query, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($query, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($query, CURLOPT_AUTOREFERER, true);
		curl_setopt($query, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($query, CURLOPT_HTTPHEADER, [ "User-Agent: VoteReward" ]);
		curl_setopt($query, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($query, CURLOPT_TIMEOUT, 5);
		$return = curl_exec($query);
		curl_close($query);
		$this->return = $return;
	}

	public function onCompletion() : void {
		Vote::getResult($this->player, $this->type, (int) $this->return);
	}
}