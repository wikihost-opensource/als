<?php
go(function () {
    do {
        foreach (['/tmp/speedtest.sock', '/tmp/speedtest-api.sock'] as $file) {
            if (!file_exists($file)) continue;
            chmod($file, 0777);
        }
    } while (false);
});
