<?php
require __DIR__ . '/helper.php';

applog('Application is starting...');

Swoole\Runtime::enableCoroutine(SWOOLE_HOOK_ALL);

Swoole\Process::signal(SIGCHLD, function ($sig) {
    //必须为false，非阻塞模式
    while ($ret = Swoole\Process::wait(false)) {
        applog("Child process exited - PID: {$ret['pid']}");
    }
});

foreach (glob(__DIR__ . '/components/*.php') as $component) {
    require_once $component;
}

\Swoole\Event::wait();
