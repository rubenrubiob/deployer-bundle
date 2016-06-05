<?php

namespace Rrb\DeployerBundle\Tests\Util;

use Rrb\DeployerBundle\Util\Misc;

/**
 * Class MiscTest
 * @package Rrb\DeployerBundle\Tests\Util
 */
class MiscTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test getConsolePath method
     */
    public function testGetConsolePath()
    {
        $this->assertEquals('bin/console', Misc::getConsolePath('2.8'));
        $this->assertEquals('app/console', Misc::getConsolePath('2.7'));
    }
}
