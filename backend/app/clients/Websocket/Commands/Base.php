<?php

namespace Client\Websocket\Commands;

use Client\Websocket\Enums\WebsocketCommandEnum as CommandMap;
use Client\Websocket as Client;
use Client\Websocket\Enums\WebsocketCommandEnum;

/**
 * Base websocket command class
 *
 * for internal use only
 */
class Base
{
    protected $commandIndex;
    protected $client;
    protected $ticket;

    public function __construct(Client $client, CommandMap $commandMap, string $ticket)
    {
        $this->client = $client;
        $this->commandIndex = $commandMap->value;
        $this->ticket = $ticket;
    }

    /**
     * End the ticket session
     *
     * @return self
     */
    public function end()
    {
        return $this->send(WebsocketCommandEnum::End->value);
    }

    /**
     * Send ticket data to client
     *
     * @param string $data
     * @return self
     */
    public function send(string $data)
    {
        $this->client->send("{$this->commandIndex}|{$this->ticket}|{$data}");
        return $this;
    }
}
