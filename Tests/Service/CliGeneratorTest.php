<?php
namespace Rrb\DeployerBundle\Tests\Service;

use Rrb\DeployerBundle\DependencyInjection\RrbDeployerExtension;
use Rrb\DeployerBundle\Util\Misc;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class CliGeneratorTest
 * @package Rrb\DeployerBundle\Tests\Service
 */
class CliGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RrbDeployerExtension
     */
    private $extension;

    /**
     * Prepare test
     */
    public function setUp()
    {
        parent::setUp();

        $this->extension = new RrbDeployerExtension();
    }

    /**
     * Test deploy method
     */
    public function testDeployDefaultConfig()
    {
        // Get a container with default config
        $container = $this->getContainer();

        $service = $container->get('rrb.deployer.cli_generator');
        $command = $service->deploy('host1');

        // Check command with default config
        $this->checkBaseCommand($command);

        // Check that options do not exist
        $this->assertEquals(false, preg_match('/verbose/', $command));
        $this->assertEquals(false, preg_match('/composer_update/', $command));
        $this->assertEquals(false, preg_match('/assets_install/', $command));
        $this->assertEquals(false, preg_match('/database_migration/', $command));

        // Check command overriding default options, i.e., forcing tasks
        $options = [
            'verbose' => true,
            'composer_update' => true,
            'assets_install' => true,
            'database_migration' => true,
        ];
        $command = $service->deploy('host1', $options);

        $this->checkBaseCommand($command);
        $this->assertEquals(true, preg_match('/composer_update:composer/', $command));
        $this->assertEquals(
            true,
            preg_match(
                sprintf('/assets_install:php,%s,web,1,/', preg_quote(Misc::getConsolePath(), '/')),
                $command
            )
        );
        $this->assertEquals(
            true,
            preg_match(
                sprintf('/database_migration:php,%s/', preg_quote(Misc::getConsolePath(), '/')),
                $command
            )
        );
    }

    /**
     * Test the generation of deploy with tasks forced to true in config
     */
    public function testDeployWithTasks()
    {
        // Get a new container with all tasks forced to be true
        $container = $this->getContainer(true);

        $service = $container->get('rrb.deployer.cli_generator');
        $command = $service->deploy('host1');

        // Check basic command
        $this->checkBaseCommand($command);

        // Check that password for host is set
        $this->assertEquals(true, preg_match('/-p password/', $command));

        // Check tasks
        $this->assertEquals(true, preg_match('/composer_update:composer-bin/', $command));
        $this->assertEquals(
            true,
            preg_match(
                sprintf('/assets_install:php,%s,webpath,1,1/', preg_quote(Misc::getConsolePath(), '/')),
                $command
            )
        );
        $this->assertEquals(
            true,
            preg_match(
                sprintf('/database_migration:php,%s/', preg_quote(Misc::getConsolePath(), '/')),
                $command
            )
        );
    }

    /**
     * @param string $command
     */
    private function checkBaseCommand($command)
    {
        $this->assertEquals(
            true,
            preg_match(
                sprintf('/^fab -f %s/', preg_quote(realpath(__DIR__.'/../../bin/fabfile.py'), '/')),
                $command
            )
        );
        $this->assertEquals(
            true,
            preg_match('/--set path=\/src\//', $command)
        );
        $this->assertEquals(
            true,
            preg_match('/-H user@server:22/', $command)
        );
        $this->assertEquals(
            true,
            preg_match('/pull:origin,master/', $command)
        );
        $this->assertEquals(
            true,
            preg_match(
                sprintf('/cache_clear:php,%s,prod/', preg_quote(Misc::getConsolePath(), '/')),
                $command
            )
        );
    }

    /**
     * Method to get the container with some config
     *
     * @param bool $tasks
     * @return ContainerBuilder
     */
    private function getContainer($tasks = false)
    {
        if (!$tasks) {
            $defaultConfig = [
                [
                    'hosts' => [
                        'host1' => [
                            'environment' => [
                                'src' => '/src/',
                            ],
                            'host' => [
                                'user' => 'user',
                                'server' => 'server',
                            ],
                            'tasks' => [],
                            'git' => [],
                        ],
                    ],
                ],
            ];
        } else {
            $defaultConfig = [
                [
                    'hosts' => [
                        'host1' => [
                            'environment' => [
                                'src' => '/src/',
                            ],
                            'host' => [
                                'user' => 'user',
                                'server' => 'server',
                                'password' => 'password',
                            ],
                            'tasks' => [
                                'database_migration' => [
                                    'enabled'   => true,
                                ],
                                'composer_update' => [
                                    'enabled'   => true,
                                    'bin'       => 'composer-bin',
                                ],
                                'assets_install' => [
                                    'enabled'   => true,
                                    'symlink'       => true,
                                    'relative'       => true,
                                    'path'       => 'webpath',
                                ],
                            ],
                            'git' => [],
                        ],
                    ],
                ],
            ];
        }

        $container = new ContainerBuilder();
        $this->extension->load($defaultConfig, $container);

        return $container;
    }
}
