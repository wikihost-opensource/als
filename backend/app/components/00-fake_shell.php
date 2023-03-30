<?php
if (env('UTILITIES_FAKESHELL', true) !== true) return;

$groupInfo = posix_getgrnam("app");
$process = new Swoole\Process(function (Swoole\Process $worker) use ($groupInfo) {
    $worker->exec('/usr/bin/ttyd', ['-g', $groupInfo['gid'], '-d', '0', '-b', '/shell', '-i', '/run/ttyd.sock', '-a', '/app/utilities/start_fakeroot.sh']);
}, false);
$process->start();
