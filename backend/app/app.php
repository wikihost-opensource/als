<?php
require __DIR__ . '/helper.php';

applog('Application is starting...');

Swoole\Runtime::enableCoroutine(SWOOLE_HOOK_ALL);

foreach (glob(__DIR__ . '/components/*.php') as $component) {
    require_once $component;
}

go(function () {
    while (true) {
        $status = Swoole\Coroutine\System::wait();
        if (isset($status['pid'])) {
            applog("Process exited - PID: {$status['pid']}");
        }
    }
});

\Swoole\Event::wait();
