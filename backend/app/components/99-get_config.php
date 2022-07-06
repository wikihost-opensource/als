<?php
$config = [
    'testfiles' => explode(" ", env('SPEEDTEST_FILE_LIST', '1MB 10MB 100MB 1GB')),
    'public_ipv4' => env('PUBLIC_IPV4'),
    'public_ipv6' => env('PUBLIC_IPV6'),
    'location' => env('LOCATION', 'Unset'),
    'bandwidth' => env('DISPLAY_BANDWIDTH'),
    'display_traffic' => env('DISPLAY_TRAFFIC', true),
    'display_speedtest' => env('ENABLE_SPEEDTEST', true),
    'utilities_ping' => env('UTILITIES_PING', true),
    'utilities_traceroute' => env('UTILITIES_TRACEROUTE', true),
    'utilities_iperf3' => env('UTILITIES_IPERF3', true),
    'utilities_iperf3_port_min' => env('UTILITIES_IPERF3_PORT_MIN', '30000'),
    'utilities_iperf3_port_max' => env('UTILITIES_IPERF3_PORT_MAX', '31000'),
];

go(function () {
    global $config;
    while (true) {
        global $reader;
        if ($reader !== null) break;
        applog('INFO: Waiting IP Database online...');
        sleep(1);
    }

    if (!$config['public_ipv4'] || !ip2long($config['public_ipv4'])) {
        $curl = curl_init('http://ifconfig.co/json');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $data = curl_exec($curl);
        $errNo = curl_errno($curl);
        curl_close($curl);
        if ($errNo !== 0) {
            applog('ERROR: Failed to get public ipv4');
        } else {
            $data = json_decode($data, true);
            if (isset($data['ip'])) {
                $config['public_ipv4'] = $data['ip'];
                $config['location'] = geo_lookup_ip($config['public_ipv4']);
            }
        }
    }
    // $data = json_decode($data, true);

    if (!$config['public_ipv6']) {
        $curl = curl_init('http://ifconfig.co/json');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V6);
        $data = curl_exec($curl);
        $errNo = curl_errno($curl);
        curl_close($curl);
        if ($errNo !== 0) {
            applog('ERROR: Failed to get public ipv6');
        } else {
            $data = json_decode($data, true);
            if (isset($data['ip'])) {
                $config['public_ipv6'] = $data['ip'];
                if (!isset($config['location'])) {
                    $config['location'] = geo_lookup_ip($config['public_ipv6']);
                }
            }
        }
    }
});
