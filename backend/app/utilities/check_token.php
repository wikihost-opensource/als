<?php
if (!isset($argv[1])) exit(1);
$token = $argv[1];


preg_match('/^[a-zA-Z0-9]+$/', $token, $matches, PREG_OFFSET_CAPTURE, 0);
if (empty($matches)) exit(1);

$tokenPath = "/app/tmp/{$token}.token";
if (!file_exists($tokenPath)) exit(1);
@unlink($tokenPath);

exit(0);
