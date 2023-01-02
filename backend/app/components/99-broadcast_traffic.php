<?php

use Client\Websocket\Enums\WebsocketCommandEnum;

if (env('DISPLAY_TRAFFIC', true) !== true) return;

$trafficCache = [];
Swoole\Timer::tick(1000, function () {
    global $trafficCache;

    preg_match_all(
        '/^\s*(?\'name\'[A-Za-z0-9]+):\s+(?\'recv\'\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(?\'send\'\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/m',
        file_get_contents('/proc/net/dev'),
        $matches,
        PREG_SET_ORDER,
        0
    );
    $interfaces = [];
    $time = time();
    $cacheData = [];
    foreach ($matches as $match) {
        if (in_array($match['name'], ['lo', 'ip6tnl0', 'tunl0', 'docker0'])) continue;
        if (strpos($match['name'], 'veth') !== false) continue;
        $cacheData[] = [
            'name' => $match['name'],
            'recv' => $match['recv'],
            'send' => $match['send'],
        ];

        $interfaces[$match['name']] = [
            'recv' => $match['recv'],
            'send' => $match['send'],
        ];
    }

    $trafficCache[] = ['time' => $time, 'data' => $cacheData];
    $trafficCache = array_reverse(array_slice(array_reverse($trafficCache), 0, 20));

    foreach (Client\Websocket::$clients as $client) {
        foreach ($interfaces as $interface => $data) {
            $client->send(implode("|", [
                WebsocketCommandEnum::StreamInterfaceTraffic->value,
                $interface,
                $data['recv'],
                $data['send']
            ]));
        }
    }
});
