<?php
spl_autoload_register(function ($class) {
    static $clientClass = 'Client\\';
    if (strpos($class, $clientClass) !== 0) return;
    $basePath = __DIR__ . '/../clients/';
    $baseClass = substr($class, strlen($clientClass));
    $baseClass = str_replace('\\', DIRECTORY_SEPARATOR, $baseClass);
    $baseClass .= '.php';
    $classPath = realpath($basePath . $baseClass);

    if (!file_exists($classPath)) return;
    require_once $classPath;
});
