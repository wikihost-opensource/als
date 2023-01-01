<?php

namespace Client;

use Client\Websocket\Enums\WebsocketCommandEnum;

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

        $this->send(WebsocketCommandEnum::Config->value . '|' . json_encode($localConfig));
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

            try {
                /** @var WebsocketCommandEnum $actionEnum */
                $action = WebsocketCommandEnum::from($action);
                $actionClassName = $action->getClass();
                if (is_null($actionClassName)) throw new \Exception('No class defined for action');

                if (!$action->isEnable()) throw new \Exception('Class is not enabled');
            } catch (\Exception $e) {
                applog("DEBUG: " . $e->getMessage());
                // no match
                return $this->close();
            }

            $ticket = $this->getTicket($action->name);

            $actionClass = new $actionClassName($this, $action, $ticket);
            go(function () use ($actionClass, $data) {
                $actionClass->execute($data);
            });
        }
    }
}
