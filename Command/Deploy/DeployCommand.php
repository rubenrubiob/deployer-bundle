<?php

namespace Rrb\DeployerBundle\Command\Deploy;

use Rrb\DeployerBundle\Command\AbstractDeployCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DeployCommand
 * @package Rrb\DeployerBundle\Command
 */
class DeployCommand extends AbstractDeployCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('rrb:deployer:deploy')
            ->setDescription('Command to deploy changes to hosts using git')
        ;

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::setStreams($input, $output);

        // Get hosts to deploy to
        $hosts = $this->getHosts();

        // Get command common options
        $options = $this->getOptions();

        // Sequentially deploy each host
        foreach ($hosts as $host) {
            // Get command to run
            $command = $this->getContainer()->get('rrb.deployer.cli_generator')->deploy($host, $options);
            $this->executeCli($host, $command);
        }
    }
}
