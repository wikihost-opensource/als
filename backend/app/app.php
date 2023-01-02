<?php
require __DIR__ . '/helper.php';

applog('Application is starting...');

Swoole\Runtime::enableCoroutine(SWOOLE_HOOK_ALL | SWOOLE_HOOK_CURL);

go(function () {
    foreach (glob(__DIR__ . '/components/*.php') as $component) {
        require_once $component;
    }
});

\Swoole\Event::wait();
