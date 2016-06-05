<?php

namespace Rrb\DeployerBundle\Util;

use Symfony\Component\HttpKernel\Kernel;

/**
 * Class Misc
 * @package Rrb\DeployerBundle\Util
 */
class Misc
{
    /**
     * Get console path, as it is different starting in 2.8 version
     *
     * @param string $version get version from Kernel by default. Used for testing purposes
     * @return string
     */
    public static function getConsolePath($version = Kernel::VERSION)
    {
        return (version_compare($version, '2.8') < 0)
            ? 'app/console'
            : 'bin/console'
            ;
    }
}
