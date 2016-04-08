<?php
/**
 * Bootstrap file for ThreadContainers.
 * Any modifications in this file should be done with exceptional care.
 */

$core = new \Surume\Runtime\Container\ThreadCore(
    __DIR__ . '/../../'
);

$providers = [
    /**
     * List of service providers that needs to be registered for your application to work correctly.
     */
];

$aliases = [
    /**
     * List of aliases that needs to be registered in IoC container for specified interfaces.
     */
];

$core->registerProviders($providers);
$core->registerAliases($aliases);

return $core;
