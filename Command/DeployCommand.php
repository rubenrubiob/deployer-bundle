<?php

namespace Rrb\DeployerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Class DeployCommand
 * @package Rrb\DeployerBundle\Command
 */
class DeployCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('rrb:deployer:deploy')
            ->setDescription('Command to deploy changes to hosts using git')
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
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $allHosts = $this->getContainer()->getParameter('rrb_deployer.hosts.all');

        // We can not do anything if there is no host
        if (empty($allHosts)) {
            throw new \Exception('No host is defined in config.yml');
        }

        // Process input hosts
        $inputHosts = $input->getArgument('hosts');
        $all = $input->getOption('all');

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

        // Process input options
        $options = [];

        // Composer update
        if ($input->getOption('composer-update')) {
            $options['composer_update'] = true;
        }

        // Assets install
        if ($input->getOption('assets-install')) {
            $options['assets_install'] = true;
        }

        // Assets install
        if ($input->getOption('database-migration')) {
            $options['database_migration'] = true;
        }

        // Verbose
        if ($output->getVerbosity() >= Output::VERBOSITY_VERBOSE) {
            $options['verbose'] = true;
        }

        $testEnvironment = !$this->getContainer()->has('kernel');

        // Sequentially deploy each host
        foreach ($hosts as $host) {
            // Get command to run
            $cliCommand = $this->getContainer()->get('rrb.deployer.cli_generator')->deploy($host, $options);

            // Only show command to execute if in test environment
            if ($testEnvironment) {
                $output->write($cliCommand.' ');
                continue;
            }

            $output->writeln(sprintf('<bg=green>  Deploying to %s  </>', $host));

            // Create process
            $process = new Process($cliCommand);
            // Process may take quite some time if it has to update composer, deploy to multiple servers...
            // So we set timeout and idle timeout from config
            $process->setTimeout($this->getContainer()->getParameter('rrb_deployer.timeout'));
            $process->setIdleTimeout($this->getContainer()->getParameter('rrb_deployer.idle_timeout'));

            $process->run(function ($type, $buffer) use ($output) {
                $output->write($buffer);
            });

            $output->writeln('');
        }
    }
}
