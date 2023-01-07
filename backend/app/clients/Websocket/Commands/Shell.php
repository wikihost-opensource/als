<?php

namespace Client\Websocket\Commands;

class Shell extends Base
{
    public function execute(array $args)
    {
        $this->client->send("{$this->commandIndex}|1|{$this->ticket}");

        file_put_contents("/app/tmp/{$this->ticket}.token", "1");

        return $this->end();
    }
}
