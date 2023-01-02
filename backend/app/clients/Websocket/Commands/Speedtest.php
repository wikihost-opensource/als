<?php

namespace Client\Websocket\Commands;

enum SpeedTestCommand: int
{
    case SendQueueStat = 2;
    case SendSubProgress = 3;
    case SendFullProgress = 4;

    case Start = 100;
    case StartPing = 101;
    case StartDownload = 102;
    case StartUpload = 103;
    case Finish = 104;

    case SendServerInfo = 200;
    case SendPing = 201;
    case SendDownloadSpeed = 202;
    case SendUploadSpeed = 203;
    case SendResult = 204;
}

class Speedtest extends Base
{
    private $results = [
        'start' => false,
        'ping' => false,
        'download' => false,
        'upload' => false,
    ];
    private static $queues = [];
    private $closed = false;
    // status management
    public $canStart = false;
    private static $running = false;
    private static $timer = null;

    public function execute(array $args)
    {
        $serverId = array_shift($args);
        $processArgs = ['--accept-license', '-f jsonl'];
        if (!is_null($serverId)) {
            if (!is_numeric($serverId)) return $this->end();
            $processArgs[] = '-s ' . $serverId;
        }
        $this->client->send("{$this->commandIndex}|1|{$this->ticket}");

        \debuglog("speedtest queuing");
        $this->join()->waitQueue();
        \debuglog("speedtest starting");
        $process = new \Process('/app/utilities/speedtest', $processArgs);
        self::$running = $process;
        while ($process->run()) {
            $content = $process->getOutput();
            if (empty(trim($content))) continue;
            // debuglog($content);
            $data = json_decode($content, true);
            if (!is_array($data)) continue;
            $this->formatOutput($data);
        }
        \debuglog("speedtest finished");
        self::$running = false;
        if (!$this->closed) $this->end();
    }

    private function join()
    {
        self::$queues[spl_object_id($this)] = $this;
        return $this;
    }

    private function waitQueue()
    {
        while (true) {
            if (is_null(self::$timer)) $this->startTimer();
            \Swoole\Coroutine\System::sleep(1);
            if ($this->canStart == true) {
                $this->send(SpeedTestCommand::SendQueueStat->value . '|0');
                break;
            }
            // debuglog(j)
            // \var_dump(spl_object_id($this));
            // \var_dump(array_keys(self::$queues));
            $pos = 0;
            $myId = spl_object_id($this);
            foreach (self::$queues as $queueMember) {
                $pos++;
                if ($queueMember instanceof self) {
                    if (spl_object_id($queueMember) == $myId) {
                        break;
                    }
                }
            }
            $total = count(self::$queues);

            $this->send(SpeedTestCommand::SendQueueStat->value . "|1|{$pos}|{$total}");
        }
    }

    private function startTimer()
    {
        debuglog("speedtest queue timer started");
        self::$timer = \Swoole\Timer::tick(1000, function () {
            if (self::$running !== false) return;

            /** @var Speedtest $selectedClient */
            $selectedClient = array_shift(self::$queues);
            if (!($selectedClient instanceof self) || !property_exists($selectedClient, 'canStart')) {
                return;
            }
            $selectedClient->canStart = true;

            if (empty(self::$queues)) {
                \Swoole\Timer::clear(self::$timer);
                debuglog("speedtest queue timer stoped");
                self::$timer = null;
            }
        });
    }

    private function formatStart($content)
    {
        $this->send(SpeedTestCommand::SendServerInfo->value . '|' . json_encode($content['server']));
    }

    private function formatPing($content)
    {
        $subPercent = bcmul(100, $content['progress'], 0);
        $totalPercent = bcmul(10, $content['progress'], 0);
        $this->send(SpeedTestCommand::SendSubProgress->value . "|{$subPercent}");
        $this->send(SpeedTestCommand::SendFullProgress->value . "|{$totalPercent}");

        $this->send(SpeedTestCommand::SendPing->value . "|{$content['latency']}");
    }

    private function formatDownload($content)
    {
        $subPercent = bcmul(100, $content['progress'], 0);
        $totalPercent = bcadd(10, bcmul(45, $content['progress'], 0));
        $this->send(SpeedTestCommand::SendSubProgress->value . "|{$subPercent}");
        $this->send(SpeedTestCommand::SendFullProgress->value . "|{$totalPercent}");

        $this->send(SpeedTestCommand::SendDownloadSpeed->value . "|{$content['bandwidth']}");
    }

    private function formatUpload($content)
    {
        $subPercent = bcmul(100, $content['progress'], 0);
        $totalPercent = bcadd(55, bcmul(45, $content['progress'], 0));
        $this->send(SpeedTestCommand::SendSubProgress->value . "|{$subPercent}");
        $this->send(SpeedTestCommand::SendFullProgress->value . "|{$totalPercent}");

        $this->send(SpeedTestCommand::SendUploadSpeed->value . "|{$content['bandwidth']}");
    }

    private function formatResult($content)
    {
        $this->send(SpeedTestCommand::SendResult->value . '|' . json_encode($content['result']));
    }


    private function formatOutput($content)
    {
        switch ($content['type']) {
            case 'testStart':
                $this->formatStart($content);
                break;
            case 'ping':
                if (!$this->results['start']) {
                    $this->results['start'] = true;
                    $this->send(SpeedTestCommand::StartPing->value);
                }
                $this->formatPing($content['ping']);
                break;
            case 'download':
                if (!$this->results['ping']) {
                    $this->results['ping'] = true;
                    $this->send(SpeedTestCommand::StartDownload->value);
                }
                $this->formatDownload($content['download']);
                break;
            case 'upload':
                if (!$this->results['download']) {
                    $this->results['download'] = true;
                    $this->send(SpeedTestCommand::StartUpload->value);
                }
                $this->formatUpload($content['upload']);
                break;
            case 'result':
                if (!$this->results['upload']) {
                    $this->results['upload'] = true;
                    $this->send(SpeedTestCommand::Finish->value);
                }
                $this->formatResult($content);
                $this->end();
                $this->closed = true;
                break;
        }
    }
}
