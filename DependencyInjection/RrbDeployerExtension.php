<?php

namespace Rrb\DeployerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class RrbDeployerExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('rrb_deployer.fabric', $config['fabric']);
        $container->setParameter('rrb_deployer.timeout', $config['timeout']);
        $container->setParameter('rrb_deployer.idle_timeout', $config['idle_timeout']);

        $hostNames = [];

        foreach ($config['hosts'] as $name => $hostConfig) {
            // Set true to host
            $container->setParameter("rrb_deployer.hosts.$name", true);

            // Set environment config
            $container->setParameter("rrb_deployer.hosts.$name.environment.src", $hostConfig['environment']['src']);
            $container->setParameter("rrb_deployer.hosts.$name.environment.php", $hostConfig['environment']['php']);
            $container->setParameter("rrb_deployer.hosts.$name.environment.env", $hostConfig['environment']['env']);

            // Set host config
            $container->setParameter("rrb_deployer.hosts.$name.host.user", $hostConfig['host']['user']);
            $container->setParameter("rrb_deployer.hosts.$name.host.password", $hostConfig['host']['password']);
            $container->setParameter("rrb_deployer.hosts.$name.host.server", $hostConfig['host']['server']);
            $container->setParameter("rrb_deployer.hosts.$name.host.port", $hostConfig['host']['port']);

            // Set git config
            $container->setParameter("rrb_deployer.hosts.$name.git.remote", $hostConfig['git']['remote']);
            $container->setParameter("rrb_deployer.hosts.$name.git.branch", $hostConfig['git']['branch']);

            // Set tasks config

            // Database migrations
            $container->setParameter(
                "rrb_deployer.hosts.$name.tasks.database_migration.enabled",
                $hostConfig['tasks']['database_migration']['enabled']
            );

            // Composer update
            $container->setParameter(
                "rrb_deployer.hosts.$name.tasks.composer.enabled",
                $hostConfig['tasks']['composer_update']['enabled']
            );
            $container->setParameter(
                "rrb_deployer.hosts.$name.tasks.composer.bin",
                $hostConfig['tasks']['composer_update']['bin']
            );

            // Assets install
            $container->setParameter(
                "rrb_deployer.hosts.$name.tasks.assets_install.enabled",
                $hostConfig['tasks']['assets_install']['enabled']
            );
            $container->setParameter(
                "rrb_deployer.hosts.$name.tasks.assets_install.symlink",
                $hostConfig['tasks']['assets_install']['symlink']
            );
            $container->setParameter(
                "rrb_deployer.hosts.$name.tasks.assets_install.relative",
                $hostConfig['tasks']['assets_install']['relative']
            );
            $container->setParameter(
                "rrb_deployer.hosts.$name.tasks.assets_install.path",
                $hostConfig['tasks']['assets_install']['path']
            );

            $hostNames[] = $name;
        }

        $container->setParameter('rrb_deployer.hosts.all', $hostNames);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
