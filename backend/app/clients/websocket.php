<?php

namespace Client;

class Websocket
{
    public static $clients = [];
    public static $ports = [];

    private $request, $response, $fd, $clientIp;
    private $closed = false;
    private $sendChannel = null;
    private $actionTicket = [];

    public function __construct($request, $response, $fd)
    {
        global $config;
        $localConfig = $config;

        $this->request = $request;

        $this->clientIp = $this->request->header['x-real-ip'];
        $localConfig['client_ip'] = $this->clientIp;

        $this->response = $response;
        $this->fd = $fd;
        $this->sendChannel = new \Swoole\Coroutine\Channel(100);
        go(function () {
            while (true) {
                $data = $this->sendChannel->pop(20);
                if ($data === '' || $data === false || $data === []) break;

                $this->response->push($data);
            }
        });

        $this->send('1000|' . json_encode($localConfig));
    }

    /**
     * 发送数据并加入到发送队列中
     *
     * @param mixed $data
     * @return void
     */
    public function send($data)
    {
        if ($this->closed) return false;
        $this->sendChannel->push($data);
        return;
    }

    /**
     * 关闭并清理连接
     *
     * @return void
     */
    public function close($frame = null)
    {
        if ($this->closed) return false;
        applog("Client disconnected, IP: {$this->clientIp}#{$this->fd}");
        $this->closed = true;
        $this->send(new \Swoole\WebSocket\CloseFrame());
        $this->sendChannel->push([]);
        $this->response->close();

        unset(self::$clients[$this->fd]);

        return true;
    }

    private function auth()
    {
        return true;
    }

    private function getTicket($type)
    {
        $ticket = uniqid();
        $this->actionTicket[$ticket] = $type;

        return $ticket;
    }

    private function doPing($ticket, $domain)
    {
        if (!env('UTILITIES_PING', true)) { return; }
        $host = gethostbyname($domain);
        if ($host === false || ($host == $domain && !ip2long($domain))) {
            $this->send("1|{$ticket}|0");
            return;
        }

        $total = 10;
        $process = new \Process('/bin/ping', ['-O', '-c 10', $host]);
        $hasHeader = false;
        $counter = 0;
        while ($process->run()) {
            if (!$hasHeader) {
                $hasHeader = true;
                $process->getOutput();
            }
            if ($counter >= $total) $process->getOutput();
            $content = $process->getOutput();
            if (empty($content)) continue;

            $counter++;

            preg_match_all('/icmp_seq=(\d+) ttl=(\d+) time=(\d+.\d+) ms/m', $content, $matches, PREG_SET_ORDER, 0);
            if (empty($matches)) {
                $this->send("1|{$ticket}|1|{$host}|-1");
            } else {
                $this->send("1|{$ticket}|1|{$host}|{$matches[0][1]}|{$matches[0][2]}|{$matches[0][3]}");
            }
        }

        $this->send("1|{$ticket}|0");
    }

    public function doTraceroute($ticket, $domain)
    {
        if (!env('UTILITIES_TRACEROUTE', true)) { return; }
        /** @var MaxMind\Db\Reader $reader */
        global $reader;

        $host = gethostbyname($domain);
        if ($host === false || ($host == $domain && !ip2long($domain))) {
            $this->send("2|{$ticket}|0");
            return;
        }

        $process = new \Process('/usr/bin/traceroute', [$host]);
        $currentHop = 0;
        $packetCount = 0;
        $needPacketsCount = 3;
        $currentPacketHost = null;
        $currentPacketDomain = null;
        $regexs = [
            'NodataHop' => '/^\s(?\'seq\'\d+)\s+(\*)/m',
            'Hop' => '/^\s(?\'seq\'\d+)\s+(?\'domain\'\S+) (?\'host\'\(\S+\))\s(?\'data\'.*)$/m',
            'Latency' => '/(\d+.\d+ ms|\*)/m',
            'LatencyWithHost' => '/^\s+(?\'domain\'\S+) \((?\'host\'\S+)\)  (?\'latency\'.*)$/m',
        ];
        while ($process->run()) {
            $content = $process->getOutput();
            if (empty(trim($content))) continue;
            $seqData = [];
            do {
                preg_match($regexs['NodataHop'], $content, $matches, PREG_OFFSET_CAPTURE, 0);
                if (!empty($matches)) {
                    $currentHop = $matches['seq'][0];
                    $packetCount = substr_count($content, '*');
                    for ($x = 1; $x <= $packetCount; $x++) {
                        $seqData[] = "{$currentHop}|0|0|0";
                    }
                    if ($packetCount == $needPacketsCount) {
                        $packetCount = 0;
                    }
                    break;
                }

                // 有节点数据
                preg_match($regexs['Hop'], $content, $matches, PREG_OFFSET_CAPTURE, 0);
                if (!empty($matches)) {
                    $packetCount = 0;
                    $currentHop = $matches['seq'][0];
                    $currentPacketHost = str_replace('(', '', $matches['host'][0]);
                    $currentPacketHost = str_replace(')', '', $currentPacketHost);
                    $currentPacketDomain = $matches['domain'][0];

                    preg_match_all($regexs['Latency'], $content, $matches, PREG_SET_ORDER, 0);
                    if (!empty($matches)) {
                        foreach ($matches as $match) {
                            if ($match[1] == '*') {
                                $seqData[] = "{$currentHop}|{$currentPacketDomain}|{$currentPacketHost}|0";
                            } else {
                                $match[1] = trim(str_replace('ms', '', $match[1]));
                                $seqData[] = "{$currentHop}|{$currentPacketDomain}|{$currentPacketHost}|{$match[1]}";
                            }
                            $packetCount++;
                        }
                        break;
                    }
                }

                preg_match($regexs['LatencyWithHost'], $content, $matches, PREG_OFFSET_CAPTURE, 0);
                if (!empty($matches)) {
                    $currentPacketHost = str_replace('(', '', $matches['host'][0]);
                    $currentPacketHost = str_replace(')', '', $currentPacketHost);
                    $currentPacketDomain = $matches['domain'][0];
                    preg_match_all($regexs['Latency'], $content, $matches, PREG_SET_ORDER, 0);
                    if (!empty($matches)) {
                        foreach ($matches as $match) {
                            if ($match[1] == '*') {
                                $seqData[] = "{$currentHop}|{$currentPacketDomain}|{$currentPacketHost}|0";
                            } else {
                                $match[1] = trim(str_replace('ms', '', $match[1]));
                                $seqData[] = "{$currentHop}|{$currentPacketDomain}|{$currentPacketHost}|{$match[1]}";
                            }
                            $packetCount++;
                        }
                        break;
                    }
                }

                if ($packetCount == $needPacketsCount) break;
                if ($packetCount + substr_count($content, '*') == $needPacketsCount) {
                    $packetCount = $packetCount + substr_count($content, '*');
                    for ($x = 1; $x <= $packetCount; $x++) {
                        $seqData[] = "{$currentHop}|0|0|0";
                    }
                }
            } while (false);

            if (!empty($seqData)) {
                foreach ($seqData as $data) {
                    $geo = 'No GEO';
                    $rawData = explode('|', $data);
                    if (ip2long($rawData[2])) {
                        $geoData = $reader->get($rawData[2]);
                        if ($geoData) {
                            $geo = [];
                            if (isset($geoData['country'])) {
                                $geo[] = $geoData['country']['names']['zh-CN'] ?? $geoData['country']['names']['en'];
                            }
                            if (isset($geoData['city'])) {
                                $geo[] = $geoData['city']['names']['zh-CN'] ?? $geoData['city']['names']['en'];
                            }

                            $geo = implode(' - ', $geo);
                        }
                    }
                    $data = implode('|', $rawData);
                    $this->send("2|{$ticket}|1|{$data}|{$geo}");
                }
            }
        }
        $this->send("2|{$ticket}|0");
    }

    public function startIperf3($ticket)
    {
        if (!env('UTILITIES_IPERF3', true)) { return; }
        $timeout = 60;
        $port = rand(env('UTILITIES_IPERF3_PORT_MIN', '30000'), env('UTILITIES_IPERF3_PORT_MAX', '31000'));
        while (in_array($port, self::$ports)) {
            $port = rand(env('UTILITIES_IPERF3_PORT_MIN', '30000'), env('UTILITIES_IPERF3_PORT_MAX', '31000'));
            sleep(.1);
        }
        self::$ports[] = $port;

        $this->send("4|{$ticket}|1|{$port}|{$timeout}");
        $process = new \Process('/usr/bin/timeout', [$timeout, '/usr/bin/iperf3', '-s', '-p', $port]);

        while ($process->run()) {
            $content = $process->getOutput();
            if (empty(trim($content))) continue;
            $this->send("4|{$ticket}|2|{$content}");
        }

        unset(self::$ports[array_search($port, self::$ports)]);

        $this->send("4|{$ticket}|0");
    }

    public function process()
    {
        if (!$this->auth()) return false;
        while (true) {
            $frame = @$this->response->recv();
            if ($frame === false || $frame === '') {
                return $this->close($frame);
            }

            if ($frame instanceof \Swoole\WebSocket\CloseFrame) {
                return $this->close($frame);
            }

            // 心跳手动处理
            if ($frame->opcode == 9) {
                $pingFrame = new \Swoole\WebSocket\Frame;
                $pingFrame->data = $frame->data;
                $pingFrame->opcode = WEBSOCKET_OPCODE_PONG;
                $this->send($pingFrame);
                continue;
            }
            if ($frame->opcode == 10) {
                continue;
            }

            $data = explode('|', trim($frame->data));
            $action = array_shift($data);
            switch ($action) {
                case 1:
                    // 1 == ping
                    $host = array_shift($data);
                    $ticket = $this->getTicket('ping');
                    $this->send("1|1|{$host}|{$ticket}");
                    go(function () use ($ticket, $host) {
                        $this->doPing($ticket, $host);
                    });
                    break;

                case 2:
                    // 2 == traceroute
                    $host = array_shift($data);
                    $ticket = $this->getTicket('traceroute');
                    $this->send("2|1|{$host}|{$ticket}");
                    go(function () use ($ticket, $host) {
                        $this->doTraceroute($ticket, $host);
                    });
                    break;
                case 4:
                    // 4 == iperf3
                    $ticket = $this->getTicket('iperf3');
                    $this->send("4|1|{$ticket}");
                    go(function () use ($ticket) {
                        $this->startIperf3($ticket);
                    });
                    break;
                default:
                    return $this->close();
            }
        }
    }
}
