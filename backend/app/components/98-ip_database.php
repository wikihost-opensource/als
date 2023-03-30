<?php
$reader = null;
go(function () {
    global $reader;
    $maxmind_license = env('MAXMIND_KEY');

    if ($maxmind_license) {
        maxmind_update($maxmind_license);

        // 30 (day) * 86400 (to seconds) * 1000 (to milliseconds)
        Swoole\Timer::tick(30 * 86400 * 1000, function () use ($maxmind_license) {
            global $reader;
            $result = maxmind_update($maxmind_license);
            if ($result) {
                $reader = new MaxMind\Db\Reader('/app/ip.mmdb');
            }
        });
    }


    if (file_exists('/app/ip.mmdb')) {
        applog("INFO: Checking exists IP Database...");
        try {
            $reader = new MaxMind\Db\Reader('/app/ip.mmdb');
            $reader->get('1.1.1.1');
            applog("INFO: Checking exists IP Database...Done");
            return;
        } catch (\Exception $e) {
            if (isset($reader)) $reader->close();
            unlink('/app/ip.mmdb');
        }
    }
    copy('/app/assets/GeoLite2-City.mmdb.txz', '/GeoIP.mmdb.txz');
    applog("INFO: Uncompressing IP Database...");
    execute('/bin/tar Jxvf /GeoIP.mmdb.txz');
    applog("INFO: Uncompressing IP Database...Done");
    unlink('/GeoIP.mmdb.txz');
    rename('/GeoLite2-City.mmdb', '/app/ip.mmdb');
    $reader = new MaxMind\Db\Reader('/app/ip.mmdb');
});
