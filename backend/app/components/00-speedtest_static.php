<?php
if (env('ENABLE_SPEEDTEST', true)) {
    applog('Working with speedtest static file(s)...');

    $speedtestFiles = [];
    $multiplier = env('IEC_FORMAT', 'true') ? 1024 : 1000;
    $sizeMap = ['KB', 'MB', 'GB', 'TB'];
    $baseBufferSize = pow($multiplier, 2);
    foreach (explode(" ", env('SPEEDTEST_FILE_LIST', '1MB 10MB 100MB 1GB')) as $speedtestSize) {
        if (empty($speedtestSize)) continue;

        preg_match('/(\d+)(KB|MB|GB|TB)/m', $speedtestSize, $matches, PREG_OFFSET_CAPTURE, 0);
        if (empty($matches)) continue;
        $targetSize = $matches[1][0] * pow($multiplier, array_search($matches[2][0], $sizeMap) + 1);
        $path = '/app/webspaces/speedtest-static/' . $speedtestSize . '.test';
        if (file_exists($path) && filesize($path) == $targetSize) {
            applog('Skip create speedtest file with size: ' . $speedtestSize . ', File exists and size match');
            continue;
        }
        applog('Creating speedtest file with size: ' . $speedtestSize . ' ...');
        $random = fopen('/dev/urandom', 'r');
        $speedtestFd = fopen($path, 'w+');
        while ($targetSize != 0) {
            if ($targetSize >= $baseBufferSize) {
                $readSize = $baseBufferSize;
            } else {
                $readSize = $targetSize;
            }
            $targetSize -= $readSize;
            fwrite($speedtestFd, fread($random, $readSize));
        }
        fclose($speedtestFd);
        fclose($random);
    }
}