<?php

namespace Rrb\DeployerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

/**
 * Class AbstractDeployCommand
 * @package Rrb\DeployerBundle\Command
 */
abstract class AbstractDeployCommand extends ContainerAwareCommand
{
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * Set common arguments
     */
    protected function configure()
    {
        $this
            ->addArgument(
                'hosts',
                InputArgument::IS_ARRAY,
                'Host(s) name(s) defined in config.yml to deploy to (separate multiple hosts with a space). If omitted, first host defined will be used.'
            )
            ->addOption(
                'all',
                null,
                InputOption::VALUE_NONE,
                'If set, it will deploy to all servers defined in config.yml. It overrides hosts argument.'
            )
            ->addOption(
                'composer-update',
                null,
                InputOption::VALUE_NONE,
                'If set, it will update composer. It overrides value in config.yml.'
            )
            ->addOption(
                'assets-install',
                null,
                InputOption::VALUE_NONE,
                'If set, it will install assets. It overrides value in config.yml.'
            )
            ->addOption(
                'database-migration',
                null,
                InputOption::VALUE_NONE,
                'If set, it will execute migrations of Doctrine. It overrides value in config.yml.'
            )
        ;
    }

    /**
     * @return array|mixed
     * @throws \Exception
     */
    protected function getHosts()
    {
        $allHosts = $this->getContainer()->getParameter('rrb_deployer.hosts.all');

        // We can not do anything if there is no host
        if (empty($allHosts)) {
            throw new \Exception('No host is defined in config.yml');
        }

        // Process input hosts
        $inputHosts = $this->input->getArgument('hosts');
        $all = $this->input->getOption('all');

        if ($all != false) {
            // If all options is provided, we have to deploy to all servers, ignoring arguments
            $hosts = $allHosts;
        } elseif ($inputHosts === null || empty($inputHosts)) {
            // If no host is provided as argument, we have to deploy to the first server defined in config.yml
            $hosts = [$allHosts[0]];
        } else {
            // If a list of hosts is provided, we have to check that they are defined in config.yml
            foreach ($inputHosts as $inputHost) {
                if (!in_array($inputHost, $allHosts)) {
                    throw new \Exception(sprintf('Host %s is not defined in config.yml', $inputHost));
                }
            }

            $hosts = $inputHosts;
        }

        return $hosts;
    }

    /**
     * @return array
     */
    protected function getOptions()
    {
        // Process input options
        $options = [];

        // Composer update
        if ($this->input->getOption('composer-update')) {
            $options['composer_update'] = true;
        }

        // Assets install
        if ($this->input->getOption('assets-install')) {
            $options['assets_install'] = true;
        }

        // Assets install
        if ($this->input->getOption('database-migration')) {
            $options['database_migration'] = true;
        }

        // Verbose
        if ($this->output->getVerbosity() >= Output::VERBOSITY_VERBOSE) {
            $options['verbose'] = true;
        }

        return $options;
    }

    /**
     * Executes command for a host
     *
     * @param string $host
     * @param string $command
     */
    protected function executeCli($host, $command)
    {
        $output = $this->output;

        $testEnvironment = !$this->getContainer()->has('kernel');

        // Only show command to execute if in test environment
        if ($testEnvironment) {
            $output->write($command.' ');

            return;
        }

        $output->writeln(sprintf('<bg=green>  Deploying to %s  </>', $host));

        // Create process
        $process = new Process($command);
        $processInput = new InputStream();
        $process->setInput($processInput);
        // Process may take quite some time if it has to update composer, deploy to multiple servers...
        // So we set timeout and idle timeout from config
        $process->setTimeout($this->getContainer()->getParameter('rrb_deployer.timeout'));
        $process->setIdleTimeout($this->getContainer()->getParameter('rrb_deployer.idle_timeout'));
        $process->run(function ($type, $buffer) use ($output, $processInput) {
            $output->write($buffer);
        });

        $output->writeln('');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function setStreams(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }
}
