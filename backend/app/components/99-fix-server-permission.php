<?php

go(function () {
    foreach ([
        '/run/speedtest.sock' => env('ENABLE_SPEEDTEST', true),
        '/run/speedtest-api.sock' => true,
        '/run/ttyd.sock' => env('UTILITIES_FAKESHELL', true)
    ] as $file => $isEnable) {
        if (!$isEnable)  continue;
        while (true) {
            // applog("Waiting sock file: " . $file);
            if (!file_exists($file)) {
                sleep(1);
                continue;
            }
            break;
        }
        chmod($file, 0770);
        chown($file, "root");
        chgrp($file, "app");
    }
});
