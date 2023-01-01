<?php

namespace Client\Websocket\Commands;

class IPerf3 extends Base
{
    public function execute(array $args)
    {
        $this->client->send("{$this->commandIndex}|1|{$this->ticket}");
        $timeout = 60;

        $port = rand(env('UTILITIES_IPERF3_PORT_MIN', '30000'), env('UTILITIES_IPERF3_PORT_MAX', '31000'));
        while (in_array($port, $this->client::$ports)) {
            $port = rand(env('UTILITIES_IPERF3_PORT_MIN', '30000'), env('UTILITIES_IPERF3_PORT_MAX', '31000'));
            sleep(.1);
        }
        $this->client::$ports[] = $port;

        $this->send("1|{$port}|{$timeout}");
        $process = new \Process('/usr/bin/timeout', [$timeout, '/usr/bin/iperf3', '-s', '-p', $port]);

        while ($process->run()) {
            $content = $process->getOutput();
            if (empty(trim($content))) continue;
            $this->send("2|{$content}");
        }

        unset($this->client::$ports[array_search($port, $this->client::$ports)]);


        $this->end();
    }
}
