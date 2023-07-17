<?php

namespace NCore\task\async;

use pocketmine\scheduler\AsyncTask;

class SendMessageTask extends AsyncTask
{
    public function __construct(private string $webhookUrl, private $content)
    {
    }

    public function onRun(): void
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $this->webhookUrl);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $this->content);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        curl_exec($curl);
    }
}