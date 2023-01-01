<?php

go(function () {
    $server = new Swoole\Coroutine\Http\Server('unix:///tmp/speedtest-api.sock');
    $server->handle('/', function (Swoole\Http\Request $request, Swoole\Http\Response $response) {
        $response->header('Server', 'webserver/1.0');
        applog('Client incoming, IP:' . $request->header['x-real-ip']);
        // 拒绝非 ws 连接
        if (!isset($request->header['connection'])) {
            applog('Client denied');
            $response->end("Websocket is required");
            return;
        }
        //
        // 发 ws 握手
        // $response->header('Sec-WebSocket-Protocol', 'looking-glass');
        if ($response->upgrade() === false) {
            applog('Client failed to upgrade websocket');
            return;
        }
        // ws 客户端加入
        $connection = new Client\Websocket($request, $response, $response->fd);
        Client\Websocket::$clients[$response->fd] = $connection;

        // 处理 ws 数据
        go(function () use ($connection) {
            $connection->process();
        });
    });
    $server->start();
});

Swoole\Timer::tick(10000, function () {
    foreach (Client\Websocket::$clients as $client) {
        $frame = new Swoole\WebSocket\Frame();
        $frame->opcode = WEBSOCKET_OPCODE_PING;
        $frame->data = 'ping';
        $client->send($frame);
    }
});
