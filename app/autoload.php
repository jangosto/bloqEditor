<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
use Composer\Autoload\ClassLoader;

/**
 * @var ClassLoader $loader_common
 */
@$loader_common = require __DIR__.'/autoload_common.php';

/**
 * @var ClassLoader $loader
 */
$loader = require __DIR__.'/../vendor/autoload.php';

if ($loader_common) {
    AnnotationRegistry::registerLoader(array($loader_common, 'loadClass'));
}

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

return $loader;
