<?php
applog('Working with nginx config...');
copy(__DIR__ . '/../config/nginx/nginx.conf', '/etc/nginx/nginx.conf');

$basicConfBasePath = __DIR__ . '/../config/nginx/';
$basicConfPath = $basicConfBasePath . 'speedtest_nohostname.conf';

$hostname = env('BIND_HOSTNAME');
if ($hostname) {
    $basicConfPath = $basicConfBasePath . 'speedtest.conf';
}

$config = file_get_contents($basicConfPath);

$config = str_replace('%HTTP_PORT%', env('HTTP_PORT', 80), $config);
$config = str_replace('%LISTEN_IP%', env('LISTEN_IP', '0.0.0.0'), $config);

@mkdir('/etc/nginx/snippets');
foreach (glob(__DIR__ . '/../config/nginx/snippets/*') as $file) {
    copy($file, '/etc/nginx/snippets/' . basename($file));
}

file_put_contents('/etc/nginx/http.d/speedtest.conf', $config);
list($dump, $exitCode) = execute('nginx -t');
if ($exitCode !== 0) {
    applog('ERR: Invalid web server config detect, application quitting...');
    exit(1);
}

$process = new Swoole\Process(function (Swoole\Process $worker) {
    $worker->exec('/usr/sbin/nginx', []);
});
$process->start();
