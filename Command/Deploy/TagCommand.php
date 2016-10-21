<?php

namespace Rrb\DeployerBundle\Command\Deploy;

use Rrb\DeployerBundle\Command\AbstractDeployCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class TagCommand
 * @package Rrb\DeployerBundle\Command
 */
class TagCommand extends AbstractDeployCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('rrb:deployer:tag')
            ->setDescription('Command to checkout a tag using git')
            ->addOption(
                'tag',
                't',
                InputOption::VALUE_REQUIRED,
                'Tag number to checkout, if any.'
            )
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

        // Assets install
        if ($this->input->getOption('tag')) {
            $options['tag'] = $this->input->getOption('tag');
        }

        // Sequentially deploy each host
        foreach ($hosts as $host) {
            // Get command to run
            $command = $this->getContainer()->get('rrb.deployer.cli_generator')->tag($host, $options);
            $this->executeCli($host, $command);
        }
    }
}
