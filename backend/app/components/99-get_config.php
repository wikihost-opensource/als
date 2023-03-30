<?php
$config = [
    'testfiles' => explode(" ", trim(env('SPEEDTEST_FILE_LIST', '1MB 10MB 100MB 1GB'))),
    'public_ipv4' => env('PUBLIC_IPV4'),
    'public_ipv6' => env('PUBLIC_IPV6'),
    'location' => env('LOCATION', false),
    'bandwidth' => env('DISPLAY_BANDWIDTH'),
    'display_traffic' => env('DISPLAY_TRAFFIC', true),
    'display_speedtest' => env('ENABLE_SPEEDTEST', true),
    'utilities_ping' => env('UTILITIES_PING', true),
    'utilities_traceroute' => env('UTILITIES_TRACEROUTE', true),

    'utilities_iperf3' => env('UTILITIES_IPERF3', true),
    'utilities_iperf3_port_min' => env('UTILITIES_IPERF3_PORT_MIN', '30000'),
    'utilities_iperf3_port_max' => env('UTILITIES_IPERF3_PORT_MAX', '31000'),

    'utilities_speedtestdotnet' => env('UTILITIES_SPEEDTESTDOTNET', true),
    'utilities_fakeshell' => env('UTILITIES_FAKESHELL', true),

    // 赞助商信息
    'sponsor_message' => '',
];

// 空的字符串分割出来也会有一个空元素, 判断出来然后置空
if (trim($config['testfiles'][0]) === '') $config['testfiles'] = [];

go(function () {
    global $config;
    $sponsor_message = env('SPONSOR_MESSAGE', '');
    if (file_exists($sponsor_message)) {
        // 如果 SPONSOR_MESSAGE 是文件且文件存在则读取文件的内容
        applog('INFO: Reading sponsor message from file: ' . $sponsor_message);
        $sponsor_message = file_get_contents($sponsor_message);
    } elseif (filter_var($sponsor_message, FILTER_VALIDATE_URL)) {
        // 如果 SPONSOR_MESSAGE 是 URL 则下载内容
        applog('INFO: Downloading sponsor message from url: ' . $sponsor_message);
        [$errNo, $data] = _wget($sponsor_message);
        if ($errNo !== 0) {
            applog('ERROR: Could not download sponsor message');
            exit(1);
        }
        $update_url = $sponsor_message;
        $sponsor_message = $data;
        $update_interval = env('SPONSOR_MESSAGE_UPDATE_INTERVAL', 3600);

        if ($update_interval > 0) {
            applog('INFO: sponsor message will auto update in ' . $update_interval . ' seconds, from url: ' . $update_url);
            $update_interval = $update_interval * 1000;
            Swoole\Timer::tick($update_interval, function () use ($update_url) {
                global $config;
                applog('INFO: Downloading sponsor message from url: ' . $update_url);
                [$errNo, $data] = _wget($update_url);
                if ($errNo !== 0) {
                    applog('ERROR: Could not download sponsor message');
                    return;
                }
                $config['sponsor_message'] = $data;
            });
        }
    }

    // 如果 SPONSOR_MESSAGE 没有被设置, 那原文是什么则是什么
    $config['sponsor_message'] = $sponsor_message;
});


go(function () {
    global $config;
    while (true) {
        global $reader;
        if ($reader !== null) break;
        applog('INFO: Waiting IP Database online...');
        sleep(1);
    }

    if (!$config['public_ipv4'] || !ip2long($config['public_ipv4'])) {
        [$errNo, $data] = _wget('http://ifconfig.co/json', [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]);
        if ($errNo !== 0) {
            applog('ERROR: Failed to get public ipv4');
        } else {
            $data = json_decode($data, true);
            if (isset($data['ip'])) {
                $config['public_ipv4'] = $data['ip'];
            }
        }
    }

    if (!$config['public_ipv6']) {
        [$errNo, $data] = _wget('http://ifconfig.co/json', [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V6]);
        if ($errNo !== 0) {
            applog('ERROR: Failed to get public ipv6');
        } else {
            $data = json_decode($data, true);
            if (isset($data['ip'])) {
                $config['public_ipv6'] = $data['ip'];
            }
        }
    }

    if ($config['public_ipv4'] && !$config['location']) {
        $config['location'] = geo_lookup_ip($config['public_ipv4']);
    }

    if ($config['public_ipv6'] && !$config['location']) {
        $config['location'] = geo_lookup_ip($config['public_ipv6']);
    }

    if ($config['location'] === false) {
        $config['location'] = 'Unset';
    }
});
