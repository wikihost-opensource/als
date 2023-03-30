<?php
$reader = null;
go(function () {
    global $reader;
    $maxmind_license = env('MAXMIND_KEY');

    if ($maxmind_license) {
        $fp = fopen('/tmp/maxmind.tar.gz', 'w');

        applog("INFO: Downloading IP Database...");

        [$errCode, $_, $httpCode] =  _wget(
            "https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&license_key=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&suffix=tar.gz",
            [
                CURLOPT_RETURNTRANSFER => 0,
                CURLOPT_FILE => $fp,
            ]
        );
        if ($httpCode === 200) {
            applog("INFO: Downloaded IP Database, Verifing...");
            execute('/bin/tar zxvf /tmp/maxmind.tar.gz -C /tmp');
            $files = glob('/tmp/*/*.mmdb');
            $dbFile = array_pop($files);
            unlink('/app/ip.mmdb');
            copy($dbFile, 'app/ip.mmdb');
        } else {
            applog("ERROR: Failed to download IP Database, fallback to local...");
        }
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
