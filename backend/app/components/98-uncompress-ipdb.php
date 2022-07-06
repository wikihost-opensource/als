<?php
$reader = null;
go(function () {
    global $reader;
    if (file_exists('/app/ip.mmdb')) {
        applog("Checking exists IP Database...");
        try {
            $reader = new MaxMind\Db\Reader('/app/ip.mmdb');
            $reader->get('1.1.1.1');
            applog("Checking exists IP Database...Done");
            return;
        } catch (\Exception $e) {
            if (isset($reader)) $reader->close();
            unlink('/app/ip.mmdb');
        }
    }
    copy('/app/assets/GeoLite2-City.mmdb.txz', '/GeoIP.mmdb.txz');
    applog("Uncompressing IP Database...");
    execute('/bin/tar Jxvf /GeoIP.mmdb.txz');
    applog("Uncompressing IP Database...Done");
    unlink('/GeoIP.mmdb.txz');
    rename('/GeoLite2-City.mmdb', '/app/ip.mmdb');
    $reader = new MaxMind\Db\Reader('/app/ip.mmdb');
});
