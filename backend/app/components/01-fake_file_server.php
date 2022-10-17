<?php
go(function () {
    if (!env('ENABLE_SPEEDTEST', true)) { return; }
    $server = new Swoole\Coroutine\Http\Server('unix:///tmp/speedtest.sock');
    $server->handle('/upload', function ($request, $response) {
        $response->header('server', 'webserver/1.0');
        if (isset($request->get['cors'])) {
            $response->header('Access-Control-Allow-Origin', '*');
            $response->header('Access-Control-Allow-Methods', 'GET, POST');
            $response->header('Access-Control-Allow-Headers', 'Content-Encoding, Content-Type');
        }
        $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, s-maxage=0, post-check=0, pre-check=0');
        $response->header('Pragma', 'no-cache');
        $response->header('Connection', 'keep-alive');
        $response->end();
    });

    $server->handle('/download', function ($request, $response) {
        $headers = ['HTTP/1.1 200 OK'];
        $socket = $response->socket;
        if (isset($request->get['cors'])) {
            $headers[] = 'Access-Control-Allow-Origin: *';
            $headers[] = 'Access-Control-Allow-Methods: GET, POST';
        }
        // Indicate a file download
        $headers[] = 'Content-Description: File Transfer';
        $headers[] = 'Content-Type: application/octet-stream';
        $headers[] = 'Content-Disposition: attachment; filename=random.dat';
        $headers[] = 'Content-Transfer-Encoding: binary';

        $headers[] = 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0, s-maxage=0';
        $headers[] = 'Cache-Control: post-check=0, pre-check=0';
        $headers[] = 'Pragma: no-cache';
        $response->detach();
        $headers = implode("\r\n", $headers);
        $socket->send($headers);
        $socket->send("\r\n\r\n");

        $chunks = null;
        if (!array_key_exists('ckSize', $request->get ?? []) || !ctype_digit($request->get['ckSize']) || (int) $request->get['ckSize'] <= 0) {
            $chunks = 4;
        } else {
            $chunks = (int) $request->get['ckSize'] > 1024 ? 1024 : $request->get['ckSize'];
        }

        $data = openssl_random_pseudo_bytes(1048576);

        for ($i = 0; $i < $chunks; $i++) {
            $socket->send($data);
        }
        return $socket->close();
    });
    $server->start();
});
