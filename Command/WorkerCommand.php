<?php

namespace TechnikaIt\SqsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TechnikaIt\SqsBundle\Service\Queue;
use TechnikaIt\SqsBundle\Service\Worker;

/**
 * Class WorkerCommand
 * @package TechnikaIt\SqsBundle\Command
 */
class WorkerCommand extends ContainerAwareCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('TechnikaIt:sqs:worker')
            ->addArgument('name', InputArgument::REQUIRED, 'Queue name', null)
            ->addOption('messages', 'm', InputOption::VALUE_OPTIONAL, 'Messages to retrive', 0)
            ->setDescription('Starts a worker that will listen on a specified SQS queue');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        if (!$this->getContainer()->has(sprintf('TechnikaIt.sqs.%s', $name))) {
            throw new \InvalidArgumentException(sprintf('Queue [%s] does not found.', $name));
        }
        $nb = $input->getOption('messages');
        if ($nb < 0) {
            throw new \InvalidArgumentException("The -m option should be null or greater than 0");
        }

        $io = new SymfonyStyle($input, $output);
        $io->title(sprintf('Start listening <comment>%s</comment>', $name));

        /** @var Queue $queue */
        $queue = $this->getContainer()->get(sprintf('TechnikaIt.sqs.%s', $name));

        /** @var Worker $worker */
        $worker = $this->getContainer()->get('TechnikaIt.sqs.queue_worker');
        $worker->start($queue, $nb);
    }
}
