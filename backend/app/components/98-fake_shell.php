<?php
if (env('UTILITIES_FAKESHELL', true) !== true) return;


$process = new Swoole\Process(function (Swoole\Process $worker) {
    $worker->exec('/usr/bin/ttyd', ['-b', '/shell', '-i', '/run/ttyd.sock', '-a', '/app/utilities/start_fakeroot.sh']);
});
$process->start();