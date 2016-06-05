<?php
namespace Rrb\DeployerBundle\Tests\DependencyInjection;

use Rrb\DeployerBundle\DependencyInjection\RrbDeployerExtension;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class RrbDeployerExtensionTest
 * @package Rrb\DeployerBundle\Tests\DependencyInjection
 */
class RrbDeployerExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RrbDeployerExtension
     */
    private $extension;

    /**
     * @var string
     */
    private $bundleRoot;

    /**
     * Prepare test
     */
    public function setUp()
    {
        parent::setUp();

        $this->extension = $this->getExtension();
        $this->bundleRoot = 'rrb_deployer';
    }

    /**
     * Test default configuration values
     */
    public function testMissingHostConfig()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            sprintf('The child node "hosts" at path "%s" must be configured.', $this->bundleRoot)
        );

        $container = $this->getContainer();
        $this->extension->load(array(), $container);
    }

    /**
     * Test missing environment.src
     */
    public function testMissingEnvironmentSrcConfig()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            sprintf('The child node "src" at path "%s.hosts.test.environment" must be configured.', $this->bundleRoot)
        );

        $emptyConfig = [
            [
                'hosts' => [
                    'test' => [
                        'environment' => [],
                        'tasks' => [],
                        'git' => [],
                    ],
                ],
            ],
        ];

        $container = $this->getContainer();
        $this->extension->load($emptyConfig, $container);
    }

    /**
     * Test missing host.user
     */
    public function testMissingHostUserConfig()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            sprintf('The child node "user" at path "%s.hosts.test.host" must be configured.', $this->bundleRoot)
        );

        $emptyConfig = [
            [
                'hosts' => [
                    'test' => [
                        'environment' => [
                            'src' => 'foo',
                        ],
                        'host' => [],
                        'tasks' => [],
                        'git' => [],
                    ],
                ],
            ],
        ];

        $container = $this->getContainer();
        $this->extension->load($emptyConfig, $container);
    }

    /**
     * Test missing host.server
     */
    public function testMissingHostServerConfig()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            sprintf('The child node "server" at path "%s.hosts.test.host" must be configured.', $this->bundleRoot)
        );

        $emptyConfig = [
            [
                'hosts' => [
                    'test' => [
                        'environment' => [
                            'src' => 'foo',
                        ],
                        'host' => [
                            'user' => 'bar',
                        ],
                        'tasks' => [],
                        'git' => [],
                    ],
                ],
            ],
        ];

        $container = $this->getContainer();
        $this->extension->load($emptyConfig, $container);
    }

    /**
     * Test default values
     */
    public function testDefaultConfig()
    {
        $hosts = [
            'test1',
            'test2',
            'test3',
        ];

        $defaultConfig = [
            [
                'hosts' => [],
            ],
        ];

        // Prepare config for 3 hosts
        foreach ($hosts as $host) {
            $defaultConfig['hosts']['hosts'][$host] = [
                'environment' => [
                    'src' => sprintf('/src/%s/', $host),
                ],
                'host' => [
                    'user' => sprintf('%s-user', $host),
                    'server' => sprintf('%s-server', $host),
                ],
                'tasks' => [],
                'git' => [],
            ];
        }

        $container = $this->getContainer();
        $this->extension->load($defaultConfig, $container);

        // Test global values
        $this->assertEquals(true, $container->hasParameter('rrb_deployer.fabric'));
        $this->assertEquals('fab', $container->getParameter('rrb_deployer.fabric'));

        $this->assertEquals(true, $container->hasParameter('rrb_deployer.timeout'));
        $this->assertEquals(3600, $container->getParameter('rrb_deployer.timeout'));

        $this->assertEquals(true, $container->hasParameter('rrb_deployer.idle_timeout'));
        $this->assertEquals(600, $container->getParameter('rrb_deployer.idle_timeout'));

        $this->assertEquals(true, $container->hasParameter('rrb_deployer.hosts.all'));
        $this->assertEquals($hosts, $container->getParameter('rrb_deployer.hosts.all'));

        // Test per host config
        foreach ($hosts as $host) {
            // Test existence of host
            $this->assertEquals(true, $container->hasParameter("rrb_deployer.hosts.$host"));
            $this->assertEquals(true, $container->getParameter("rrb_deployer.hosts.$host"));

            // Test environment config
            $this->assertEquals(true, $container->hasParameter("rrb_deployer.hosts.$host.environment.src"));
            $this->assertEquals(true, $container->hasParameter("rrb_deployer.hosts.$host.environment.php"));
            $this->assertEquals(true, $container->hasParameter("rrb_deployer.hosts.$host.environment.env"));

            $this->assertEquals(
                sprintf('/src/%s/', $host),
                $container->getParameter("rrb_deployer.hosts.$host.environment.src")
            );
            $this->assertEquals('php', $container->getParameter("rrb_deployer.hosts.$host.environment.php"));
            $this->assertEquals('prod', $container->getParameter("rrb_deployer.hosts.$host.environment.env"));

            // Test host config
            $this->assertEquals(true, $container->hasParameter("rrb_deployer.hosts.$host.host.user"));
            $this->assertEquals(true, $container->hasParameter("rrb_deployer.hosts.$host.host.password"));
            $this->assertEquals(true, $container->hasParameter("rrb_deployer.hosts.$host.host.server"));
            $this->assertEquals(true, $container->hasParameter("rrb_deployer.hosts.$host.host.port"));

            $this->assertEquals(
                sprintf('%s-user', $host),
                $container->getParameter("rrb_deployer.hosts.$host.host.user")
            );
            $this->assertEquals(null, $container->getParameter("rrb_deployer.hosts.$host.host.password"));
            $this->assertEquals(
                sprintf('%s-server', $host),
                $container->getParameter("rrb_deployer.hosts.$host.host.server")
            );
            $this->assertEquals(22, $container->getParameter("rrb_deployer.hosts.$host.host.port"));

            // Test git config
            $this->assertEquals(true, $container->hasParameter("rrb_deployer.hosts.$host.git.remote"));
            $this->assertEquals(true, $container->hasParameter("rrb_deployer.hosts.$host.git.branch"));

            $this->assertEquals('origin', $container->getParameter("rrb_deployer.hosts.$host.git.remote"));
            $this->assertEquals('master', $container->getParameter("rrb_deployer.hosts.$host.git.branch"));

            // Test tasks

            // Test database migrations
            $this->assertEquals(
                true,
                $container->hasParameter("rrb_deployer.hosts.$host.tasks.database_migration.enabled")
            );
            $this->assertEquals(
                false,
                $container->getParameter("rrb_deployer.hosts.$host.tasks.database_migration.enabled")
            );

            // Test composer update
            $this->assertEquals(true, $container->hasParameter("rrb_deployer.hosts.$host.tasks.composer.enabled"));
            $this->assertEquals(true, $container->hasParameter("rrb_deployer.hosts.$host.tasks.composer.bin"));

            $this->assertEquals(false, $container->getParameter("rrb_deployer.hosts.$host.tasks.composer.enabled"));
            $this->assertEquals('composer', $container->getParameter("rrb_deployer.hosts.$host.tasks.composer.bin"));

            // Test assets install
            $this->assertEquals(
                true,
                $container->hasParameter("rrb_deployer.hosts.$host.tasks.assets_install.enabled")
            );
            $this->assertEquals(
                true,
                $container->hasParameter("rrb_deployer.hosts.$host.tasks.assets_install.symlink")
            );
            $this->assertEquals(
                true,
                $container->hasParameter("rrb_deployer.hosts.$host.tasks.assets_install.relative")
            );
            $this->assertEquals(true, $container->hasParameter("rrb_deployer.hosts.$host.tasks.assets_install.path"));

            $this->assertEquals(
                false,
                $container->getParameter("rrb_deployer.hosts.$host.tasks.assets_install.enabled")
            );
            $this->assertEquals(
                true,
                $container->getParameter("rrb_deployer.hosts.$host.tasks.assets_install.symlink")
            );
            $this->assertEquals(
                false,
                $container->getParameter("rrb_deployer.hosts.$host.tasks.assets_install.relative")
            );
            $this->assertEquals('web', $container->getParameter("rrb_deployer.hosts.$host.tasks.assets_install.path"));
        }
    }

    /**
     * Test global config
     */
    public function testOverrideGlobalConfig()
    {

        $defaultConfig = [
            [
                'fabric'    => 'fabric',
                'timeout'   => 1000,
                'idle_timeout'  => 100,
                'hosts'     => [
                    'host1'     => [
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

        $container = $this->getContainer();
        $this->extension->load($defaultConfig, $container);

        // Test global values
        $this->assertEquals(true, $container->hasParameter('rrb_deployer.fabric'));
        $this->assertEquals('fabric', $container->getParameter('rrb_deployer.fabric'));

        $this->assertEquals(true, $container->hasParameter('rrb_deployer.timeout'));
        $this->assertEquals(1000, $container->getParameter('rrb_deployer.timeout'));

        $this->assertEquals(true, $container->hasParameter('rrb_deployer.idle_timeout'));
        $this->assertEquals(100, $container->getParameter('rrb_deployer.idle_timeout'));
    }

    /**
     * Test overriding environment config
     */
    public function testOverrideEnvironmentConfig()
    {

        $defaultConfig = [
            [
                'hosts'     => [
                    'host1'     => [
                        'environment' => [
                            'php' => 'php7.0',
                            'src' => '/src/',
                            'env' => 'dev',
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

        $container = $this->getContainer();
        $this->extension->load($defaultConfig, $container);

        // Test environment config
        $this->assertEquals(true, $container->hasParameter("rrb_deployer.hosts.host1.environment.src"));
        $this->assertEquals(true, $container->hasParameter("rrb_deployer.hosts.host1.environment.php"));
        $this->assertEquals(true, $container->hasParameter("rrb_deployer.hosts.host1.environment.env"));

        $this->assertEquals('php7.0', $container->getParameter("rrb_deployer.hosts.host1.environment.php"));
        $this->assertEquals('/src/', $container->getParameter("rrb_deployer.hosts.host1.environment.src"));
        $this->assertEquals('dev', $container->getParameter("rrb_deployer.hosts.host1.environment.env"));
    }

    /**
     * Test overriding host config
     */
    public function testOverrideHostConfig()
    {

        $defaultConfig = [
            [
                'hosts'     => [
                    'host1'     => [
                        'environment' => [
                            'src' => '/src/',
                        ],
                        'host' => [
                            'user' => 'user',
                            'password' => 'password',
                            'server' => 'server',
                            'port' => 2222,
                        ],
                        'tasks' => [],
                        'git' => [],
                    ],
                ],
            ],
        ];

        $container = $this->getContainer();
        $this->extension->load($defaultConfig, $container);

        // Test host config
        $this->assertEquals(true, $container->hasParameter("rrb_deployer.hosts.host1.host.user"));
        $this->assertEquals(true, $container->hasParameter("rrb_deployer.hosts.host1.host.password"));
        $this->assertEquals(true, $container->hasParameter("rrb_deployer.hosts.host1.host.server"));
        $this->assertEquals(true, $container->hasParameter("rrb_deployer.hosts.host1.host.port"));

        $this->assertEquals('user', $container->getParameter("rrb_deployer.hosts.host1.host.user"));
        $this->assertEquals('password', $container->getParameter("rrb_deployer.hosts.host1.host.password"));
        $this->assertEquals('server', $container->getParameter("rrb_deployer.hosts.host1.host.server"));
        $this->assertEquals(2222, $container->getParameter("rrb_deployer.hosts.host1.host.port"));
    }

    /**
     * Test overriding git config
     */
    public function testOverrideGitConfig()
    {

        $defaultConfig = [
            [
                'hosts'     => [
                    'host1'     => [
                        'environment' => [
                            'src' => '/src/',
                        ],
                        'host' => [
                            'user' => 'user',
                            'server' => 'server',
                        ],
                        'tasks' => [],
                        'git' => [
                            'remote' => 'remote_origin',
                            'branch' => 'dev',
                        ],
                    ],
                ],
            ],
        ];

        $container = $this->getContainer();
        $this->extension->load($defaultConfig, $container);

        // Test git config
        $this->assertEquals(true, $container->hasParameter("rrb_deployer.hosts.host1.git.remote"));
        $this->assertEquals(true, $container->hasParameter("rrb_deployer.hosts.host1.git.branch"));

        $this->assertEquals('remote_origin', $container->getParameter("rrb_deployer.hosts.host1.git.remote"));
        $this->assertEquals('dev', $container->getParameter("rrb_deployer.hosts.host1.git.branch"));
    }

    /**
     * Test overriding git config
     */
    public function testOverrideTasksConfig()
    {
        $defaultConfig = [
            [
                'hosts'     => [
                    'host1'     => [
                        'environment' => [
                            'src' => '/src/',
                        ],
                        'host' => [
                            'user' => 'user',
                            'server' => 'server',
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
                                'symlink'       => false,
                                'relative'       => true,
                                'path'       => '/web/web',
                            ],
                        ],
                        'git' => [],
                    ],
                ],
            ],
        ];

        $container = $this->getContainer();
        $this->extension->load($defaultConfig, $container);

        // Test tasks

        // Test database migrations
        $this->assertEquals(
            true,
            $container->hasParameter("rrb_deployer.hosts.host1.tasks.database_migration.enabled")
        );
        $this->assertEquals(
            true,
            $container->getParameter("rrb_deployer.hosts.host1.tasks.database_migration.enabled")
        );

        // Test composer update
        $this->assertEquals(true, $container->hasParameter("rrb_deployer.hosts.host1.tasks.composer.enabled"));
        $this->assertEquals(true, $container->hasParameter("rrb_deployer.hosts.host1.tasks.composer.bin"));

        $this->assertEquals(true, $container->getParameter("rrb_deployer.hosts.host1.tasks.composer.enabled"));
        $this->assertEquals('composer-bin', $container->getParameter("rrb_deployer.hosts.host1.tasks.composer.bin"));

        // Test assets install
        $this->assertEquals(
            true,
            $container->hasParameter("rrb_deployer.hosts.host1.tasks.assets_install.enabled")
        );
        $this->assertEquals(
            true,
            $container->hasParameter("rrb_deployer.hosts.host1.tasks.assets_install.symlink")
        );
        $this->assertEquals(
            true,
            $container->hasParameter("rrb_deployer.hosts.host1.tasks.assets_install.relative")
        );
        $this->assertEquals(true, $container->hasParameter("rrb_deployer.hosts.host1.tasks.assets_install.path"));

        $this->assertEquals(
            true,
            $container->getParameter("rrb_deployer.hosts.host1.tasks.assets_install.enabled")
        );
        $this->assertEquals(
            false,
            $container->getParameter("rrb_deployer.hosts.host1.tasks.assets_install.symlink")
        );
        $this->assertEquals(
            true,
            $container->getParameter("rrb_deployer.hosts.host1.tasks.assets_install.relative")
        );
        $this->assertEquals('/web/web', $container->getParameter("rrb_deployer.hosts.host1.tasks.assets_install.path"));
    }

    /**
     * @return RrbDeployerExtension
     */
    private function getExtension()
    {
        return new RrbDeployerExtension();
    }

    /**
     * @return ContainerBuilder
     */
    private function getContainer()
    {
        return new ContainerBuilder();
    }
}
