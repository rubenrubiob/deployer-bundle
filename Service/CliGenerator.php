<?php
namespace Rrb\DeployerBundle\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Rrb\DeployerBundle\Util\Misc;

/**
 * Class CliGenerator
 * @package Rrb\DeployerBundle\Service
 */
class CliGenerator
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * CliGenerator constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $host
     * @param array  $options
     * @return string
     */
    public function deploy($host, $options = [])
    {
        $configOptions = [
            'composer_update'       => $this->container->getParameter(
                "rrb_deployer.hosts.$host.tasks.composer.enabled"
            ),
            'assets_install'        => $this->container->getParameter(
                "rrb_deployer.hosts.$host.tasks.assets_install.enabled"
            ),
            'database_migration'    => $this->container->getParameter(
                "rrb_deployer.hosts.$host.tasks.database_migration.enabled"
            ),
        ];

        // Options provided as arguments have priority over options in config
        $options += $configOptions;

        // Set base command, that will be updated with options if there is any
        $cli = sprintf(
            '%s %s',
            $this->generateBaseCommand($host, $options),
            $this->generatePullTask($host)
        );

        // Check if we have to generate the composer update task
        if ($options['composer_update']) {
            $cli = sprintf(
                '%s %s',
                $cli,
                $this->generateComposerUpdateTask($host)
            );
        }

        // Check if we have to generate the assets install task
        if ($options['assets_install']) {
            $cli = sprintf(
                '%s %s',
                $cli,
                $this->generateAssetsInstallTask($host)
            );
        }

        // Check if we have to execute the database migration task
        if ($options['database_migration']) {
            $cli = sprintf(
                '%s %s',
                $cli,
                $this->generateDatabaseMigrationTask($host)
            );
        }

        // Last command is always cache clear
        $cli = sprintf(
            '%s %s',
            $cli,
            $this->generateCacheClearTask($host)
        );

        return $cli;
    }

    /**
     * @param string $host
     * @return string
     */
    private function generatePullTask($host)
    {
        return sprintf(
            'pull:%s,%s',
            $this->container->getParameter("rrb_deployer.hosts.$host.git.remote"),
            $this->container->getParameter("rrb_deployer.hosts.$host.git.branch")
        );
    }

    /**
     * @param string $host
     * @return string
     */
    private function generateCacheClearTask($host)
    {
        return sprintf(
            'cache_clear:%s,%s,%s',
            $this->container->getParameter("rrb_deployer.hosts.$host.environment.php"),
            Misc::getConsolePath(),
            $this->container->getParameter("rrb_deployer.hosts.$host.environment.env")
        );
    }

    /**
     * @param string $host
     * @return string
     */
    private function generateComposerUpdateTask($host)
    {
        return sprintf(
            'composer_update:%s',
            $this->container->getParameter("rrb_deployer.hosts.$host.tasks.composer.bin")
        );
    }

    /**
     * @param string $host
     * @return string
     */
    private function generateAssetsInstallTask($host)
    {
        return sprintf(
            'assets_install:%s,%s,%s,%s,%s',
            $this->container->getParameter("rrb_deployer.hosts.$host.environment.php"),
            Misc::getConsolePath(),
            $this->container->getParameter("rrb_deployer.hosts.$host.tasks.assets_install.path"),
            $this->container->getParameter("rrb_deployer.hosts.$host.tasks.assets_install.symlink"),
            $this->container->getParameter("rrb_deployer.hosts.$host.tasks.assets_install.relative")
        );
    }

    /**
     * @param string $host
     * @return string
     */
    private function generateDatabaseMigrationTask($host)
    {
        return sprintf(
            'database_migration:%s,%s',
            $this->container->getParameter("rrb_deployer.hosts.$host.environment.php"),
            Misc::getConsolePath()
        );
    }

    /**
     * @param $host
     * @return string
     */
    private function generateHostString($host)
    {
        return sprintf(
            '%s@%s:%s',
            $this->container->getParameter("rrb_deployer.hosts.$host.host.user"),
            $this->container->getParameter("rrb_deployer.hosts.$host.host.server"),
            $this->container->getParameter("rrb_deployer.hosts.$host.host.port")
        );
    }

    /**
     * @param string $host
     * @param array  $options
     * @return string
     */
    private function generateBaseCommand($host, array $options)
    {
        $command = '%s -f %s --set path=%s -H %s';
        if (array_key_exists('verbose', $options) && $options['verbose']) {
            $command = '%s -f %s --set path=%s,verbose -H %s';
        }

        $command = sprintf(
            $command,
            $this->container->getParameter('rrb_deployer.fabric'),
            realpath(__DIR__.'/../bin/fabfile.py'),
            $this->container->getParameter("rrb_deployer.hosts.$host.environment.src"),
            $this->generateHostString($host)
        );

        if ($this->container->getParameter("rrb_deployer.hosts.$host.host.password") !== null) {
            $command = sprintf(
                '%s -p %s',
                $command,
                $this->container->getParameter("rrb_deployer.hosts.$host.host.password")
            );
        }

        return $command;
    }
}
