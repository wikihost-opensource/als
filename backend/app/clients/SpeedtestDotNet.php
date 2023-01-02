<?php

namespace Client;

use \Swoole\Coroutine\Channel;
use \Swoole\Coroutine\WaitGroup;

class SpeedtestDotNet
{
    private array $queues = [];
    private array $caches = [];
    private int $queueSize;
    private WaitGroup $wg;

    public function __construct($queueSize = 1000)
    {
        $this->queueSize = $queueSize;
        $this->wg = new WaitGroup();
    }

    public function start()
    {
    }

    public function shutdown()
    {
    }
}
