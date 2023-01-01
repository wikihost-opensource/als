<?php
if (env('DISPLAY_TRAFFIC', true) !== true) return;
Swoole\Timer::tick(1000, function () {
    preg_match_all(
        '/^\s*(?\'name\'[A-Za-z0-9]+):\s+(?\'recv\'\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(?\'send\'\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/m',
        file_get_contents('/proc/net/dev'),
        $matches,
        PREG_SET_ORDER,
        0
    );
    $interfaces = [];
    foreach ($matches as $match) {
        if (in_array($match['name'], ['lo', 'ip6tnl0', 'tunl0', 'docker0'])) continue;
        if (strpos($match['name'], 'veth') !== false) continue;
        $interfaces[$match['name']] = [
            'recv' => $match['recv'],
            'send' => $match['send'],
        ];
    }

    foreach (Client\Websocket::$clients as $client) {
        foreach ($interfaces as $interface => $data) {
            $client->send(implode("|", [
                100,
                $interface,
                $data['recv'],
                $data['send']
            ]));
        }
    }
});
