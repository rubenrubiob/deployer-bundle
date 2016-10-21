<?php

namespace Rrb\DeployerBundle\Tests\Command\Deploy;

use Rrb\DeployerBundle\Command\Deploy\DeployCommand;
use Rrb\DeployerBundle\DependencyInjection\RrbDeployerExtension;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Output\Output;

/**
 * Class DeployCommandTest
 * @package Rrb\DeployerBundle\Tests\Command\Deploy
 */
class DeployCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RrbDeployerExtension
     */
    private $extension;

    /**
     * @var array
     */
    private $config;

    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * @var Command
     */
    private $command;

    /**
     * @var CommandTester
     */
    private $commandTester;

    /**
     * Prepare test
     */
    public function setUp()
    {
        parent::setUp();

        $this->extension = new RrbDeployerExtension();
        $this->config = [
            [
                'hosts' => [
                    'host1' => [
                        'environment' => [
                            'src' => 'srchost1',
                        ],
                        'host' => [
                            'user' => 'userhost1',
                            'server' => 'serverhost1',
                        ],
                        'tasks' => [],
                        'git' => [],
                    ],
                    'host2' => [
                        'environment' => [
                            'src' => 'srchost2',
                        ],
                        'host' => [
                            'user' => 'userhost2',
                            'server' => 'serverhost2',
                        ],
                        'tasks' => [],
                        'git' => [],
                    ],
                    'host3' => [
                        'environment' => [
                            'src' => 'srchost2',
                        ],
                        'host' => [
                            'user' => 'userhost2',
                            'server' => 'serverhost2',
                        ],
                        'tasks' => [],
                        'git' => [],
                    ],
                ],
            ],
        ];
    }

    /**
     * Test arguments for DeployCommand
     */
    public function testCommandArguments()
    {
        $this->container = $this->getContainer();
        $this->createApplication();

        $service = $this->container->get('rrb.deployer.cli_generator');

        // Test that, if all is given, we would deploy to all hosts
        $this->commandTester->execute(
            [
                'command' => $this->command->getName(),
                '--all' => null,
                'hosts' => ['host2', 'host3'],
            ]
        );

        $this->assertEquals(
            sprintf('%s %s %s ', $service->deploy('host1'), $service->deploy('host2'), $service->deploy('host3')),
            $this->commandTester->getDisplay()
        );


        // Test deploy to multiple hosts, but not all
        $this->commandTester->execute(
            array(
                'command' => $this->command->getName(),
                'hosts' => ['host2', 'host3'],
            )
        );

        $this->assertEquals(
            sprintf('%s %s ', $service->deploy('host2'), $service->deploy('host3')),
            $this->commandTester->getDisplay()
        );


        // Test deploy without arguments, and check that only the first host will be deployed
        $this->commandTester->execute(
            array(
                'command' => $this->command->getName(),
            )
        );

        $this->assertEquals(
            sprintf('%s ', $service->deploy('host1')),
            $this->commandTester->getDisplay()
        );
    }

    /**
     * Test options for DeployCommand
     */
    public function testCommandOptions()
    {
        $this->container = $this->getContainer();
        $this->createApplication();

        $service = $this->container->get('rrb.deployer.cli_generator');

        // Test that, if options are given, we would deploy using them
        $options = [
            'verbose' => true,
            'composer_update' => true,
            'assets_install' => true,
            'database_migration' => true,
        ];


        $this->commandTester->execute(
            [
                'command' => $this->command->getName(),
                '--composer-update' => null,
                '--assets-install' => null,
                '--database-migration' => null,
            ],
            [
                'verbosity' => Output::VERBOSITY_VERBOSE,
            ]
        );

        $this->assertEquals(
            sprintf('%s ', $service->deploy('host1', $options)),
            $this->commandTester->getDisplay()
        );
    }

    /**
     * Test Exception when supplying a non existent host
     */
    public function testCommandHostNonExistent()
    {
        $this->container = $this->getContainer();
        $this->createApplication();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Host foo is not defined in config.yml');

        // Supply a non existent host
        $this->commandTester->execute(
            array(
                'command' => $this->command->getName(),
                'hosts' => ['host1', 'foo'],
            )
        );
    }

    /**
     * Test Exception there is no host defined in config.yml
     */
    public function testCommandNoHostDefined()
    {
        $this->container = $this->getContainer();
        $this->container->setParameter('rrb_deployer.hosts.all', []);
        $this->createApplication();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No host is defined in config.yml');

        // Supply a non existent host
        $this->commandTester->execute(
            array(
                'command' => $this->command->getName(),
                'hosts' => ['host1', 'foo'],
            )
        );
    }

    private function createApplication()
    {
        $kernel = new \AppKernel('test', true);
        $kernel->boot();

        $application = new Application($kernel);
        $application->add(new DeployCommand());

        $this->command = $application->find('rrb:deployer:deploy');
        $this->command->setContainer($this->container);
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * Method to get the container with some config
     *
     * @return ContainerBuilder
     */
    private function getContainer()
    {
        $container = new ContainerBuilder();
        $this->extension->load($this->config, $container);

        return $container;
    }
}
